import { Hono } from 'hono'
import { authMiddleware } from '../auth.js'

const creator = new Hono()

// Apply auth middleware to all routes in this file
creator.use('*', authMiddleware)

creator.get('/dashboard', async (c) => {
  const user = c.get('user')
  const userId = user.sub // user.id is stored in sub

  try {
    const db = c.env.DB
    
    // Check if profile is completed
    const profile = await db.prepare("SELECT * FROM creator_profiles WHERE user_id = ?").bind(userId).first()
    
    if (!profile) {
      return c.json({ success: true, profile_completed: false })
    }

    // Batch query for performance (D1 supports batching!)
    const batchResults = await db.batch([
      db.prepare("SELECT id, title, last_message_at FROM chat_sessions WHERE user_id = ? AND is_active = 1 ORDER BY last_message_at DESC LIMIT 5").bind(userId),
      db.prepare("SELECT id, title, platform, status FROM content_ideas WHERE user_id = ? ORDER BY created_at DESC LIMIT 5").bind(userId),
      db.prepare("SELECT COUNT(*) as count FROM saved_content WHERE user_id = ?").bind(userId),
      db.prepare("SELECT COUNT(*) as count FROM chat_sessions WHERE user_id = ?").bind(userId),
      db.prepare("SELECT COUNT(*) as count FROM content_ideas WHERE user_id = ?").bind(userId),
      db.prepare("SELECT COUNT(*) as count FROM content_calendar WHERE user_id = ? AND publish_date >= date('now')").bind(userId),
      db.prepare("SELECT platform_name, followers FROM creator_platforms WHERE user_id = ? AND is_active = 1").bind(userId),
      db.prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0").bind(userId)
    ])

    const recentChats = batchResults[0].results
    const recentIdeas = batchResults[1].results
    const savedCount = batchResults[2].results[0].count
    const chatCount = batchResults[3].results[0].count
    const ideasCount = batchResults[4].results[0].count
    const calendarCount = batchResults[5].results[0].count
    const platforms = batchResults[6].results
    const unreadNotifications = batchResults[7].results[0].count

    const displayName = profile.display_name || profile.creator_name || user.username

    return c.json({
      success: true,
      profile_completed: true,
      data: {
        display_name: displayName,
        profile,
        recentChats,
        recentIdeas,
        counts: {
          saved: savedCount,
          chats: chatCount,
          ideas: ideasCount,
          calendar: calendarCount,
          unread_notifications: unreadNotifications
        },
        platforms
      }
    })

  } catch (error) {
    return c.json({ success: false, error: error.message }, 500)
  }
})

export default creator
