/**
 * CreatorAI - Gemini API Utility
 */

export class GeminiClient {
    constructor(apiKey, model = 'gemini-1.5-flash') {
        this.apiKey = apiKey;
        this.model = model;
        this.baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';
    }

    async callGemini(systemPrompt, contents, jsonMode = false) {
        if (!this.apiKey) {
            return {
                success: false,
                error: 'AI service is not configured. Please add GEMINI_API_KEY to your environment.'
            };
        }

        const url = `${this.baseUrl}${this.model}:generateContent?key=${this.apiKey}`;
        
        const body = {
            contents,
            generationConfig: {
                temperature: 0.7,
                topP: 0.9,
                maxOutputTokens: 8192,
            }
        };

        if (systemPrompt) {
            body.systemInstruction = {
                parts: [{ text: systemPrompt }]
            };
        }

        if (jsonMode) {
            body.generationConfig.responseMimeType = 'application/json';
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(body)
            });

            const result = await response.json();

            if (!response.ok) {
                const errorMsg = result.error?.message || 'Gemini API Error';
                return { success: false, error: errorMsg, http_code: response.status };
            }

            const text = result.candidates?.[0]?.content?.parts?.[0]?.text || '';
            
            if (!text) {
                const blockReason = result.candidates?.[0]?.finishReason;
                if (blockReason === 'SAFETY') {
                    return { success: false, error: 'The response was blocked by safety filters. Please rephrase your message.' };
                }
                return { success: false, error: 'Empty response from Gemini API.' };
            }

            return { success: true, response: text };

        } catch (error) {
            return { success: false, error: error.message };
        }
    }

    async chat(message, creatorContext, chatHistory = []) {
        const contents = chatHistory.map(msg => ({
            role: msg.role === 'assistant' ? 'model' : 'user',
            parts: [{ text: msg.content }]
        }));

        const lastContent = contents[contents.length - 1];
        const alreadyIncluded = lastContent && lastContent.role === 'user' && lastContent.parts[0].text === message;

        if (!alreadyIncluded) {
            contents.push({
                role: 'user',
                parts: [{ text: message }]
            });
        }

        return this.callGemini(creatorContext, contents, false);
    }

    async generate(prompt, creatorContext, jsonMode = false) {
        const contents = [{
            role: 'user',
            parts: [{ text: prompt }]
        }];
        return this.callGemini(creatorContext, contents, jsonMode);
    }
}
