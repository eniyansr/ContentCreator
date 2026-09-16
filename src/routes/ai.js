import { Hono } from 'hono'
import { authMiddleware } from '../auth.js'
import { GeminiClient } from '../utils/gemini.js'

const ai = new Hono()

ai.use('*', authMiddleware)

async function buildCreatorContext(db, userId) {
    const profile = await db.prepare("SELECT * FROM creator_profiles WHERE user_id = ?").bind(userId).first()
    if (!profile) return "You are a helpful AI assistant for content creators."

    let context = `You are an expert AI assistant for a content creator.\n`
    context += `The creator's name is ${profile.display_name || profile.creator_name}.\n`
    
    if (profile.primary_niche) {
        context += `Their primary niche is: ${profile.primary_niche}.\n`
    }
    
    try {
        const types = JSON.parse(profile.creator_types || '[]')
        if (types.length > 0) {
            context += `They identify as: ${types.join(', ')}.\n`
        }
    } catch(e) {}
    
    if (profile.target_audience) {
        context += `Their target audience is: ${profile.target_audience}.\n`
    }
    
    if (profile.content_style) {
        context += `Their content style/tone is: ${profile.content_style}.\n`
    }

    return context
}

ai.get('/sessions', async (c) => {
    const userId = c.get('user').sub
    const db = c.env.DB
    try {
        const { results } = await db.prepare(
            "SELECT id, title, last_message_at, created_at FROM chat_sessions WHERE user_id = ? AND is_active = 1 ORDER BY last_message_at DESC LIMIT 50"
        ).bind(userId).all()
        return c.json({ success: true, data: results })
    } catch (e) {
        return c.json({ success: false, error: e.message }, 500)
    }
})

ai.get('/messages', async (c) => {
    const userId = c.get('user').sub
    const db = c.env.DB
    const sessionId = c.req.query('session_id')
    if (!sessionId) return c.json({ success: false, error: 'session_id is required' }, 400)

    try {
        const session = await db.prepare("SELECT * FROM chat_sessions WHERE id = ? AND user_id = ?").bind(sessionId, userId).first()
        if (!session) return c.json({ success: false, error: 'Session not found' }, 404)

        const { results: messages } = await db.prepare(
            "SELECT id, role, message AS content, created_at FROM chat_messages WHERE session_id = ? ORDER BY created_at ASC LIMIT 200"
        ).bind(sessionId).all()

        return c.json({ success: true, data: { session, messages } })
    } catch (e) {
        return c.json({ success: false, error: e.message }, 500)
    }
})

ai.post('/chat', async (c) => {
    const userId = c.get('user').sub
    const db = c.env.DB
    const { message, session_id } = await c.req.json()

    if (!message) return c.json({ success: false, error: 'Message is required' }, 400)

    try {
        let currentSessionId = session_id
        if (!currentSessionId) {
            const title = message.substring(0, 60)
            const result = await db.prepare("INSERT INTO chat_sessions (user_id, title) VALUES (?, ?)").bind(userId, title).run()
            currentSessionId = result.meta.last_row_id
        } else {
            const session = await db.prepare("SELECT * FROM chat_sessions WHERE id = ? AND user_id = ?").bind(currentSessionId, userId).first()
            if (!session) return c.json({ success: false, error: 'Session not found' }, 404)
        }

        await db.prepare("INSERT INTO chat_messages (session_id, user_id, role, message) VALUES (?, ?, 'user', ?)").bind(currentSessionId, userId, message).run()
        await db.prepare("UPDATE chat_sessions SET last_message_at = CURRENT_TIMESTAMP, message_count = message_count + 1 WHERE id = ?").bind(currentSessionId).run()

        const { results: history } = await db.prepare("SELECT role, message AS content FROM chat_messages WHERE session_id = ? ORDER BY created_at ASC LIMIT 20").bind(currentSessionId).all()

        const context = await buildCreatorContext(db, userId)
        const gemini = new GeminiClient(c.env.GEMINI_API_KEY)
        
        const aiResponse = await gemini.chat(message, context, history)

        if (aiResponse.success) {
            await db.prepare("INSERT INTO chat_messages (session_id, user_id, role, message) VALUES (?, ?, 'assistant', ?)").bind(currentSessionId, userId, aiResponse.response).run()
            await db.prepare("UPDATE chat_sessions SET last_message_at = CURRENT_TIMESTAMP, message_count = message_count + 1 WHERE id = ?").bind(currentSessionId).run()
            
            const words = aiResponse.response.split(' ').length
            await db.prepare("INSERT INTO ai_usage_logs (user_id, action, details) VALUES (?, 'chat', ?)").bind(userId, JSON.stringify({ tokens_used: words + 50 })).run()
            
            return c.json({ success: true, session_id: currentSessionId, response: aiResponse.response })
        }

        return c.json(aiResponse, 500)
    } catch (error) {
        return c.json({ success: false, error: error.message }, 500)
    }
})

ai.post('/generate', async (c) => {
    const userId = c.get('user').sub
    const db = c.env.DB
    const { content_type, platform, topic, tone, length, idea_id } = await c.req.json()

    if (!topic && !idea_id) return c.json({ success: false, error: 'Please provide a topic or select an idea' }, 400)

    try {
        let finalTopic = topic
        let ideaContext = ""

        if (idea_id) {
            const idea = await db.prepare("SELECT * FROM content_ideas WHERE id = ? AND user_id = ?").bind(idea_id, userId).first()
            if (idea) {
                finalTopic = idea.title
                ideaContext = `Base this content on the following idea:\nTitle: ${idea.title}\nDescription: ${idea.description}\n`
            }
        }

        let prompt = ""
        if (content_type === 'script') {
            prompt = `Write a complete video script for ${platform || 'General'} about: "${finalTopic}".\n${ideaContext}`
            prompt += "Include visual/B-roll suggestions in brackets [like this].\nStructure it clearly with a Hook, Intro, Body points, and a strong Call to Action.\n"
            if (length === 'short') prompt += "Keep it short (under 60 seconds of speaking).\n"
            else if (length === 'detailed') prompt += "Make it detailed and comprehensive (3-5 minutes of speaking).\n"
        } else if (content_type === 'caption') {
            prompt = `Write an engaging social media caption for ${platform || 'General'} about: "${finalTopic}".\n${ideaContext}`
            prompt += "Include a strong hook on the first line.\nInclude a clear Call to Action at the end.\nProvide a curated list of 15-20 relevant hashtags at the bottom.\n"
        } else if (content_type === 'title') {
            prompt = `Generate 10 highly clickable, optimized titles/headlines for ${platform || 'General'} about: "${finalTopic}".\n${ideaContext}`
            prompt += "Provide a mix of styles (e.g., How-to, Listicle, Question, Curiosity gap, Direct).\nDo not write the script, ONLY provide the list of 10 titles.\n"
        } else if (content_type === 'hooks') {
            prompt = `Generate 7 powerful, attention-grabbing hooks for ${platform || 'General'} about: "${finalTopic}".\n${ideaContext}`
            prompt += "Provide a mix of hook styles. Explain briefly *why* each hook works.\n"
        } else {
            prompt = `Write content for ${platform || 'General'} about: "${finalTopic}".\n${ideaContext}`
        }

        if (tone && tone !== 'default') {
            prompt += `\nTone adjustment: Make this specifically sound ${tone}.\n`
        }

        const context = await buildCreatorContext(db, userId)
        const gemini = new GeminiClient(c.env.GEMINI_API_KEY)
        
        const aiResponse = await gemini.generate(prompt, context, false)

        if (aiResponse.success) {
            const words = aiResponse.response.split(' ').length
            await db.prepare("INSERT INTO ai_usage_logs (user_id, action, details) VALUES (?, 'generator', ?)").bind(userId, JSON.stringify({ tokens_used: words + 100 })).run()
            
            return c.json({ success: true, data: { content: aiResponse.response, topic: finalTopic } })
        }

        return c.json(aiResponse, 500)
    } catch (error) {
        return c.json({ success: false, error: error.message }, 500)
    }
})

ai.post('/ideas', async (c) => {
    const userId = c.get('user').sub
    const db = c.env.DB
    const { topic, platform, count } = await c.req.json()

    if (!topic) return c.json({ success: false, error: 'Topic is required' }, 400)

    try {
        const prompt = `Generate ${count || 5} content ideas for ${platform || 'various platforms'} about "${topic}".
Output MUST be a valid JSON array of objects, with no markdown formatting.
Each object must have these exactly string keys:
"title": The catchy title of the idea.
"description": A 2-sentence description of what the content is about.
"platform": The best platform for this idea (e.g., YouTube, TikTok, Instagram).
"format": The format (e.g., Short-form video, Carousel, Newsletter).
"difficulty": "Easy", "Medium", or "Hard".
"estimated_reach": "High", "Medium", or "Low".`

        const context = await buildCreatorContext(db, userId)
        const gemini = new GeminiClient(c.env.GEMINI_API_KEY)
        
        const aiResponse = await gemini.generate(prompt, context, true)

        if (aiResponse.success) {
            let ideas = []
            try {
                // Gemini sometimes wraps JSON in markdown block even with responseMimeType
                const cleanJson = aiResponse.response.replace(/```json\n|\n```/g, '').trim()
                ideas = JSON.parse(cleanJson)
            } catch (e) {
                return c.json({ success: false, error: 'Failed to parse AI output' }, 500)
            }

            return c.json({ success: true, data: { ideas } })
        }

        return c.json(aiResponse, 500)
    } catch (error) {
        return c.json({ success: false, error: error.message }, 500)
    }
})

ai.post('/analyze', async (c) => {
    const userId = c.get('user').sub
    const db = c.env.DB
    const { content, platform } = await c.req.json()

    if (!content) return c.json({ success: false, error: 'Content is required' }, 400)

    try {
        const prompt = `Analyze the following content for ${platform || 'general social media'}.
Output MUST be a valid JSON object with the following string keys exactly:
"score": A number from 1 to 100.
"strengths": An array of strings detailing what works well.
"weaknesses": An array of strings detailing what needs improvement.
"suggestions": An array of strings detailing actionable tips to improve it.
"estimated_engagement": "High", "Medium", or "Low".

Content to analyze:
${content}`

        const context = await buildCreatorContext(db, userId)
        const gemini = new GeminiClient(c.env.GEMINI_API_KEY)
        
        const aiResponse = await gemini.generate(prompt, context, true)

        if (aiResponse.success) {
            let analysis = null
            try {
                const cleanJson = aiResponse.response.replace(/```json\n|\n```/g, '').trim()
                analysis = JSON.parse(cleanJson)
            } catch (e) {
                return c.json({ success: false, error: 'Failed to parse AI output' }, 500)
            }

            return c.json({ success: true, data: analysis })
        }

        return c.json(aiResponse, 500)
    } catch (error) {
        return c.json({ success: false, error: error.message }, 500)
    }
})

ai.post('/repurpose', async (c) => {
    const userId = c.get('user').sub
    const db = c.env.DB
    const { content, source_platform, target_platforms } = await c.req.json()

    if (!content || !target_platforms || target_platforms.length === 0) return c.json({ success: false, error: 'Content and target platforms are required' }, 400)

    try {
        const prompt = `Repurpose the following content from ${source_platform || 'general context'} into formats for the following platforms: ${target_platforms.join(', ')}.
Output MUST be a valid JSON object where keys are the platform names, and values are the new repurposed content strings. Do not use markdown blocks for the JSON.

Content to repurpose:
${content}`

        const context = await buildCreatorContext(db, userId)
        const gemini = new GeminiClient(c.env.GEMINI_API_KEY)
        
        const aiResponse = await gemini.generate(prompt, context, true)

        if (aiResponse.success) {
            let repurposed = null
            try {
                const cleanJson = aiResponse.response.replace(/```json\n|\n```/g, '').trim()
                repurposed = JSON.parse(cleanJson)
            } catch (e) {
                return c.json({ success: false, error: 'Failed to parse AI output' }, 500)
            }

            return c.json({ success: true, data: repurposed })
        }

        return c.json(aiResponse, 500)
    } catch (error) {
        return c.json({ success: false, error: error.message }, 500)
    }
})

export default ai
