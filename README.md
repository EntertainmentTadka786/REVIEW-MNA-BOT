# 🎬 Entertainment Tadka Bot

A Telegram bot for movie searches across multiple channels with a complete movie request system.

## Features

### 🎬 Movie Search System
- Search movies across multiple channels
- Partial name matching
- Smart search with relevance scoring
- Channel type detection (Public/Private)
- Automatic forwarding with proper attribution

### 📋 Movie Request System
- `/request MovieName` command
- Natural language requests ("pls add movie")
- Duplicate request blocking (24 hours)
- Flood control (3 requests/day/user)
- Status tracking (Pending/Approved/Rejected)

### 👤 User Features
- `/myrequests` - View request status
- Personal statistics
- Daily login rewards
- Notification system

### 🧑‍💼 Admin Features
- `/pendingrequests` - View and moderate requests
- Inline approve/reject buttons
- Bulk actions
- Custom rejection reasons
- `/stats` - Detailed bot statistics

### ⚙️ Technical Features
- CSV-based database with caching
- Automatic channel post tracking
- Error logging and monitoring
- Webhook support for Render.com
- Session support for moderation

## Deployment

### Render.com (Recommended)
1. Fork/Create repository with all files
2. Go to [Render.com](https://render.com)
3. Click "New +" → "Web Service"
4. Connect your GitHub repository
5. Configure:
   - **Name:** `entertainment-tadka-bot`
   - **Environment:** `Docker`
   - **Plan:** `Free`
   - **Environment Variables:** Add `BOT_TOKEN` (from @BotFather)

6. Click "Create Web Service"
7. After deployment, visit: `https://your-service.onrender.com/?setup=1`

### Local Development
```bash
# Clone repository
git clone <your-repo>
cd entertainment-tadka-bot

# Create .env file
cp .env.example .env
# Edit .env with your bot token

# Start with Docker Compose
docker-compose up -d

# Check logs
docker-compose logs -f