#!/bin/bash
# 自動 commit & push 腳本
# 用法：./push.sh "commit 訊息"

MSG=${1:-"chore: 自動更新 $(date '+%Y-%m-%d %H:%M')"}

echo "📦 加入所有變更..."
git add .

echo "💬 Commit：$MSG"
git commit -m "$MSG"

echo "🚀 推送至 GitHub..."
git push origin main

echo "✅ 完成！"
