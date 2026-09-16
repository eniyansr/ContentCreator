import { sign, verify } from 'hono/jwt'

const JWT_SECRET = 'creatorai_super_secret_key_2026' // TODO: Use env vars in production

export const generateToken = async (user) => {
  const payload = {
    sub: user.id,
    username: user.username,
    role: user.role || 'user',
    exp: Math.floor(Date.now() / 1000) + 60 * 60 * 24 // 24 hours expiration
  }
  return await sign(payload, JWT_SECRET)
}

export const authMiddleware = async (c, next) => {
  const authHeader = c.req.header('Authorization')
  if (!authHeader || !authHeader.startsWith('Bearer ')) {
    return c.json({ success: false, error: 'Unauthorized' }, 401)
  }

  const token = authHeader.split(' ')[1]
  try {
    const decodedPayload = await verify(token, JWT_SECRET)
    c.set('user', decodedPayload)
    await next()
  } catch (e) {
    return c.json({ success: false, error: 'Invalid or expired token' }, 401)
  }
}
