#!/bin/bash
# Entertainment Tadka Bot Setup Script

echo "🎬 Entertainment Tadka Bot Setup"
echo "================================="

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "⚠️  Please run as root or with sudo"
    exit 1
fi

# Update system
echo "📦 Updating system packages..."
apt-get update -qq

# Install Docker if not installed
if ! command -v docker &> /dev/null; then
    echo "🐳 Installing Docker..."
    curl -fsSL https://get.docker.com -o get-docker.sh
    sh get-docker.sh
    rm get-docker.sh
fi

# Install Docker Compose if not installed
if ! command -v docker-compose &> /dev/null; then
    echo "📦 Installing Docker Compose..."
    curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    chmod +x /usr/local/bin/docker-compose
fi

# Create directory structure
echo "📁 Creating directory structure..."
mkdir -p backups cache data

# Set permissions
echo "🔐 Setting permissions..."
chmod 755 backups cache data
chmod 666 movies.csv users.json requests.json bot_stats.json error.log 2>/dev/null || true

# Create .env file if not exists
if [ ! -f .env ]; then
    echo "📝 Creating .env file..."
    cat > .env << EOF
# Telegram Bot Configuration
BOT_TOKEN=your_bot_token_here
ENVIRONMENT=development
ADMIN_ID=1080317415

# Channel Configuration
PUBLIC_CHANNEL_1_ID=-1003181705395
PUBLIC_CHANNEL_1_USERNAME=@EntertainmentTadka786
PUBLIC_CHANNEL_2_ID=-1002831605258
PUBLIC_CHANNEL_2_USERNAME=@threater_print_movies
PUBLIC_CHANNEL_3_ID=-1002964109368
PUBLIC_CHANNEL_3_USERNAME=@ETBackup
PRIVATE_CHANNEL_1_ID=-1003251791991
PRIVATE_CHANNEL_2_ID=-1002337293281
PRIVATE_CHANNEL_3_ID=-1003614546520
REQUEST_GROUP_ID=-1003083386043
REQUEST_GROUP_USERNAME=@EntertainmentTadka7860
API_ID=21944581
API_HASH=7b1c174a5cd3466e25a976c39a791737
EOF
    echo "⚠️  Please edit .env file and add your BOT_TOKEN"
fi

# Start Docker Compose
echo "🚀 Starting Docker Compose..."
docker-compose up -d

echo ""
echo "✅ Setup Complete!"
echo ""
echo "📊 Check status: docker-compose logs -f"
echo "🌐 Access bot: http://localhost:8080"
echo "🔗 Set webhook: http://localhost:8080/?setup=1"
echo ""
echo "📝 Next steps:"
echo "1. Edit .env file with your BOT_TOKEN"
echo "2. Restart: docker-compose restart"
echo "3. Test: http://localhost:8080/?test=1"