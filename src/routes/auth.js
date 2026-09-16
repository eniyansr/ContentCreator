import { Hono } from 'hono'
import bcrypt from 'bcryptjs'
import { generateToken } from '../auth.js'

const auth = new Hono()

auth.post('/register', async (c) => {
  const { full_name, username, email, password } = await c.req.json()
  
  if (!full_name || !username || !email || !password) {
    return c.json({ success: false, error: 'All fields are required' }, 400)
  }

  const salt = bcrypt.genSaltSync(10)
  const password_hash = bcrypt.hashSync(password, salt)

  try {
    const result = await c.env.DB.prepare(
      "INSERT INTO users (full_name, username, email, password_hash) VALUES (?, ?, ?, ?)"
    ).bind(full_name, username, email, password_hash).run()

    if (result.success) {
      return c.json({ success: true, message: 'Registration successful' })
    }
    return c.json({ success: false, error: 'Failed to register' }, 500)
  } catch (error) {
    if (error.message.includes('UNIQUE constraint failed')) {
      return c.json({ success: false, error: 'Username or email already exists' }, 409)
    }
    return c.json({ success: false, error: error.message }, 500)
  }
})

auth.post('/login', async (c) => {
  const { email, password } = await c.req.json()

  if (!email || !password) {
    return c.json({ success: false, error: 'Email and password are required' }, 400)
  }

  try {
    const user = await c.env.DB.prepare(
      "SELECT id, username, password_hash FROM users WHERE email = ?"
    ).bind(email).first()

    if (!user) {
      return c.json({ success: false, error: 'Invalid credentials' }, 401)
    }

    const isValid = bcrypt.compareSync(password, user.password_hash)
    if (!isValid) {
      return c.json({ success: false, error: 'Invalid credentials' }, 401)
    }

    const token = await generateToken(user)
    return c.json({ success: true, token, redirect: '/creator/dashboard.html' })
  } catch (error) {
    return c.json({ success: false, error: error.message }, 500)
  }
})

export default auth
