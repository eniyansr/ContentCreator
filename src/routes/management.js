import { Hono } from 'hono'
import { authMiddleware } from '../auth.js'

const management = new Hono()
management.use('*', authMiddleware)

management.get('/ideas', async (c) => {
    const userId = c.get('user').sub
    const db = c.env.DB
    try {
        const { results: savedIdeas } = await db.prepare(
            "SELECT * FROM content_ideas WHERE user_id = ? ORDER BY created_at DESC"
        ).bind(userId).all()
        
        const { results: platforms } = await db.prepare(
            "SELECT platform_name FROM creator_platforms WHERE user_id = ?"
        ).bind(userId).all()

        return c.json({ success: true, data: { savedIdeas, platforms } })
    } catch (e) {
        return c.json({ success: false, error: e.message }, 500)
    }
})

management.post('/ideas/save', async (c) => {
    const userId = c.get('user').sub
    const db = c.env.DB
    const { title, description, platform, tags, status, viral_score } = await c.req.json()

    if (!title) return c.json({ success: false, error: 'Title is required' }, 400)

    try {
        const id = await db.prepare(
            "INSERT INTO content_ideas (user_id, title, description, platform, tags, status, viral_score) VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING id"
        ).bind(userId, title, description, platform, JSON.stringify(tags || []), status || 'idea', viral_score || 0).first('id')
        
        return c.json({ success: true, id })
    } catch (e) {
        return c.json({ success: false, error: e.message }, 500)
    }
})

management.post('/ideas/status', async (c) => {
    const userId = c.get('user').sub
    const db = c.env.DB
    const { id, status } = await c.req.json()

    try {
        await db.prepare("UPDATE content_ideas SET status = ? WHERE id = ? AND user_id = ?").bind(status, id, userId).run()
        return c.json({ success: true })
    } catch (e) {
        return c.json({ success: false, error: e.message }, 500)
    }
})

management.post('/ideas/delete', async (c) => {
    const userId = c.get('user').sub
    const db = c.env.DB
    const { id } = await c.req.json()

    try {
        await db.prepare("DELETE FROM content_ideas WHERE id = ? AND user_id = ?").bind(id, userId).run()
        return c.json({ success: true })
    } catch (e) {
        return c.json({ success: false, error: e.message }, 500)
    }
})

management.post('/content/save', async (c) => {
    const userId = c.get('user').sub
    const db = c.env.DB
    const { title, content, content_type, platform } = await c.req.json()

    if (!content || !title) return c.json({ success: false, error: 'Title and content are required' }, 400)

    try {
        const id = await db.prepare(
            "INSERT INTO saved_content (user_id, title, content_type, platform, content) VALUES (?, ?, ?, ?, ?) RETURNING id"
        ).bind(userId, title, content_type || 'text', platform || 'General', content).first('id')
        
        return c.json({ success: true, id })
    } catch (e) {
        return c.json({ success: false, error: e.message }, 500)
    }
})

export default management
