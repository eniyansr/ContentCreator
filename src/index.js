import { Hono } from 'hono'
import { cors } from 'hono/cors'

const app = new Hono()

app.use('*', cors())

import authRoutes from './routes/auth.js'
import creatorRoutes from './routes/creator.js'

// Basic Health Check Endpoint
app.get('/api/health', (c) => {
  return c.json({ status: 'ok', message: 'CreatorAI Worker is running' })
})

app.route('/api/auth', authRoutes)
app.route('/api/creator', creatorRoutes)

// Database Connection Test Endpoint
app.get('/api/db-test', async (c) => {
  try {
    const { results } = await c.env.DB.prepare("SELECT * FROM users LIMIT 1").all()
    return c.json({ success: true, data: results })
  } catch (error) {
    return c.json({ success: false, error: error.message }, 500)
  }
})

// Redirect root to login
app.get('/', (c) => c.redirect('/login.html'))

export default app
