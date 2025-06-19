# PawConnect AI Chatbot Setup Guide

## Overview
This guide will help you set up the AI chatbot for your PawConnect website using ChatGPT API. The chatbot is designed to answer pet-related questions and provide customer support for your platform.

## Features
- 🤖 AI-powered responses using ChatGPT API
- 🐕 Pet-specific knowledge and guidance
- 💬 Real-time conversation with typing indicators
- 📱 Responsive design for all devices
- 💾 Chat history persistence
- ⚡ Quick action buttons for common queries
- 🎨 Beautiful, modern UI design

## Files Created
1. `chatbot.html` - Main chatbot interface
2. `chatbot.css` - Styling for the chatbot
3. `javascripts/chatbot.js` - Frontend functionality
4. `chatbot_api.php` - Backend API for ChatGPT integration
5. Floating chatbot buttons added to main pages

## Setup Instructions

### Step 1: Get OpenAI API Key
1. Go to [OpenAI Platform](https://platform.openai.com/)
2. Sign up or log in to your account
3. Navigate to "API Keys" section
4. Create a new API key
5. Copy the API key (keep it secure!)

### Step 2: Configure the API
1. Open `chatbot_api.php`
2. Find this line:
   ```php
   'openai_api_key' => 'YOUR_OPENAI_API_KEY_HERE',
   ```
3. Replace `YOUR_OPENAI_API_KEY_HERE` with your actual API key
4. Save the file

### Step 3: Test the Chatbot
1. Make sure your web server (XAMPP) is running
2. Open your browser and go to `http://localhost/pawconnect/chatbot.html`
3. Try asking questions like:
   - "How do I adopt a pet?"
   - "What subscription plans do you offer?"
   - "How do I book a vet appointment?"

### Step 4: Customize Responses (Optional)
You can customize the chatbot's knowledge by editing the system prompt in `chatbot_api.php`. The current prompt includes:
- PawConnect-specific information
- Pet care guidance
- Service explanations
- Payment methods
- Subscription details

## API Configuration Options

### Model Selection
You can change the AI model by modifying this line in `chatbot_api.php`:
```php
'openai_model' => 'gpt-3.5-turbo', // or 'gpt-4' for more advanced responses
```

### Response Settings
Adjust these parameters for different response styles:
```php
'max_tokens' => 500,      // Maximum response length
'temperature' => 0.7,     // Creativity level (0.0 = focused, 1.0 = creative)
```

## Security Considerations

### API Key Security
- Never commit your API key to version control
- Consider using environment variables for production
- Monitor your API usage to control costs

### Rate Limiting
The chatbot includes conversation history limiting to manage token usage and costs.

## Cost Management
- GPT-3.5-turbo: ~$0.002 per 1K tokens
- GPT-4: ~$0.03 per 1K tokens (input) + $0.06 per 1K tokens (output)
- Monitor usage in your OpenAI dashboard

## Troubleshooting

### Common Issues

1. **API Key Error**
   - Verify your API key is correct
   - Check if you have sufficient credits
   - Ensure the key has proper permissions

2. **CORS Errors**
   - Make sure you're accessing via a web server (not file://)
   - Check that your server supports PHP

3. **No Response**
   - Check browser console for errors
   - Verify PHP is properly configured
   - Test API connectivity

### Testing the API
You can test the API directly using curl:
```bash
curl -X POST http://localhost/pawconnect/chatbot_api.php \
  -H "Content-Type: application/json" \
  -d '{"message": "Hello, how can you help me?"}'
```

## Customization Options

### Adding New Quick Actions
Edit the quick action buttons in `chatbot.html`:
```html
<button class="quick-btn" onclick="sendQuickMessage('Your question here')">
    <i class="fas fa-icon"></i> Button Text
</button>
```

### Modifying Fallback Responses
Edit the `getFallbackResponse()` function in `chatbot_api.php` to add more specific responses.

### Styling Changes
Modify `chatbot.css` to change colors, fonts, or layout.

## Integration with Other Pages

The floating chatbot button has been added to:
- `index.html`
- `home.html`

You can add it to other pages by copying this code:
```html
<div class="chatbot-float" onclick="window.location.href='chatbot.html'">
    <i class="fas fa-robot"></i>
    <span class="chatbot-tooltip">Ask our AI Assistant</span>
</div>
```

## Performance Optimization

### Caching
- Consider implementing response caching for common questions
- Use browser localStorage for chat history (already implemented)

### Token Management
- The system limits conversation history to manage costs
- Adjust `$historyLimit` in `chatbot_api.php` as needed

## Future Enhancements

Potential improvements you could add:
1. **Voice Input/Output** - Speech recognition and synthesis
2. **Image Recognition** - Analyze pet photos for health concerns
3. **Multi-language Support** - Bengali language support
4. **Advanced Analytics** - Track common questions and user behavior
5. **Integration with Database** - Store chat logs and user preferences

## Support

If you encounter issues:
1. Check the browser console for JavaScript errors
2. Check your server's error logs
3. Verify your OpenAI API key and credits
4. Test with the fallback responses first

## Cost Estimation

For a typical usage scenario:
- 100 conversations per day
- Average 10 messages per conversation
- ~$5-15 per month with GPT-3.5-turbo
- ~$50-150 per month with GPT-4

Monitor your usage in the OpenAI dashboard and adjust settings as needed.

---

**Note**: This chatbot is designed specifically for PawConnect and includes knowledge about your services, pricing, and pet care. The AI will provide helpful, contextual responses while always recommending professional veterinary care for serious health concerns. 