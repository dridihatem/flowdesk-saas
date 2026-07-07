I want to build an AI business assistant inspired by Jarvis for my Laravel SaaS application.

The AI assistant name should be dynamically based on the SaaS name. For example, if the SaaS name is "NovaERP", the assistant should be called "NovaERP AI" or "NovaERP Assistant".

Requirements:

1. Add a dedicated AI section to the company dashboard.

2. Create a modern card similar to Jarvis from Iron Man:
   - Animated microphone icon.
   - Circular pulse effect while listening.
   - State labels:
     - Waiting to talk...
     - Listening...
     - Thinking...
     - Responding...
   - Show the assistant name.
   - Dark modern UI with blue gradient accents.

3. Add a voice button so users can speak naturally.

4. Convert speech to text and display the recognized text below the microphone.

5. Send the request to the AI backend.

6. The AI should have access to company data:
   - Clients
   - Projects
   - Invoices
   - Revenue
   - Expenses
   - Payments
   - Tasks
   - Users
   - Bookings

7. Show the AI answer inside a chat area and optionally play it using text-to-speech.

8. Add an "AI Summary" widget on the dashboard displaying:
   - Total clients
   - Active projects
   - Monthly revenue
   - Unpaid invoices
   - Growth percentage
   - AI-generated recommendations

9. Example interactions:

User:
"How much revenue did we generate this month?"

Assistant:
"This month the company generated $25,400 from 18 active projects. Revenue increased by 12% compared with last month."

User:
"Who are our top customers?"

Assistant:
"The top customers are..."

10. Add a floating card on the dashboard:

----------------------------------------------------
[ SaaSName AI ]
🤖 Waiting to talk...

Press the microphone and ask anything about
clients, projects, revenues, invoices or tasks.

🎤 Talk
----------------------------------------------------

11. Create reusable Laravel components:
- AIAssistantCard
- AIChatPanel
- AISummaryWidget
- VoiceRecorder
- ConversationHistory

12. Use:
- Laravel 12
- Livewire
- TailwindCSS
- Alpine.js
- Responsive design
- Dark mode support

13. The interface should feel like a professional Jarvis assistant for a business ERP/CRM SaaS, with smooth animations and a premium appearance.

Generate the Blade views, Livewire components, routes, controllers, database structure, and frontend code necessary to implement this feature.