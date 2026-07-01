#!/bin/bash

# Define paths and credentials
PROJECT_ROOT="/opt/kobets"
VENV_PYTHON="${PROJECT_ROOT}/venv/bin/python"
AGENTS_DIR="${PROJECT_ROOT}/agents"
WEBSITE_DIR="${PROJECT_ROOT}/website"
GITHUB_REPO_DIR="${PROJECT_ROOT}/kobets-site"
GITHUB_OWNER="RaijinCajin"
GITHUB_REPO="kobets-site"
TELEGRAM_CHAT_ID="355912537" # Your Telegram chat ID
TELEGRAM_BOT_TOKEN="$TELEGRAM_BOT_TOKEN" # Replace with your actual bot token

# 1. Run agents and update data
echo "Running agents..."
$VENV_PYTHON "${AGENTS_DIR}/fight_scraper.py"
$VENV_PYTHON "${AGENTS_DIR}/odds_scraper.py"
$VENV_PYTHON "${AGENTS_DIR}/predictor.py"
echo "Agents finished."

# 2. Check for changes in the website directory and commit to GitHub
echo "Checking for changes and committing to GitHub..."
cd "$GITHUB_REPO_DIR" || { echo "Failed to change directory to $GITHUB_REPO_DIR"; exit 1; }

# Copy the updated index.html from the website directory to the cloned repo
cp "${WEBSITE_DIR}/index.html" "${GITHUB_REPO_DIR}/index.html"

# Get current date for branch name and commit message
CURRENT_DATE=$(date +"%Y%m%d%H%M%S")
BRANCH_NAME="automated-update-${CURRENT_DATE}"
COMMIT_MESSAGE="feat: Automated data update and website refresh for ${CURRENT_DATE}"

git checkout main
git pull origin main
git checkout -b "$BRANCH_NAME"

git add "${GITHUB_REPO_DIR}/index.html"
git add "${AGENTS_DIR}/fight_scraper.py"
git add "${AGENTS_DIR}/odds_scraper.py"
git add "${AGENTS_DIR}/predictor.py"

# Only commit if there are changes
if ! git diff-index --quiet HEAD --; then
  git commit -m "$COMMIT_MESSAGE"
  git push -u origin "$BRANCH_NAME"

  # 3. Create a pull request and send Telegram message for review
  echo "Creating GitHub Pull Request..."
  PR_RESPONSE=$(gh pr create --title "$COMMIT_MESSAGE" --body "Automated data update. Please review and approve for deployment." --head "$BRANCH_NAME" --base main --json url)
  PR_URL=$(echo "$PR_RESPONSE" | jq -r '.[0].url')

  if [ -z "$PR_URL" ]; then
    echo "Failed to create PR or get PR URL."
    TELEGRAM_MESSAGE="🚨 Automated KO-Bets update failed to create a PR. Please check logs."
  else
    echo "Pull Request created: $PR_URL"
    TELEGRAM_MESSAGE="✅ New KO-Bets.com update ready for review! Please approve by replying 'approve PR $PR_URL' to this message. Review here: $PR_URL"
  fi
else
  echo "No changes detected. Skipping commit and PR creation."
  TELEGRAM_MESSAGE="ℹ️ KO-Bets.com daily update: No new data or changes detected. No PR created."
fi

# Send Telegram message
curl -s -X POST "https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/sendMessage" \
  -d "chat_id=${TELEGRAM_CHAT_ID}" \
  -d "text=${TELEGRAM_MESSAGE}"

echo "Script finished."
