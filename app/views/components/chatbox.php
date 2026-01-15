

<!-- Floating AI Chatbot CSS -->
<style>
    /* .chatbot-container{
        min-height: 100vh;

    } */
    /* Chatbot Button */
    .chatbot-toggle {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #187672 0%, #248156 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        z-index: 1000;
        transition: all 0.3s ease;
        border: none;
    }

    .chatbot-toggle:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
    }

    .chatbot-toggle i {
        color: white;
        font-size: 24px;
        transition: all 0.3s ease;
    }

    .chatbot-toggle.active i.fa-message {
        display: none;
    }

    .chatbot-toggle.active i.fa-xmark {
        display: block;
    }

    .chatbot-toggle i.fa-xmark {
        display: none;
    }

    /* Chatbot Container */
    .chatbot-container {
        position: fixed;
        bottom: 100px;
        right: 30px;
        width: 380px;
        height: 550px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        z-index: 999;
        display: none;
        flex-direction: column;
        overflow: hidden;
        animation: slideUp 0.3s ease;
        border: 1px solid #198754;
    }

    .chatbot-container.active {
        display: flex;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Chatbot Header */
    .chatbot-header {
        background: linear-gradient(135deg, #187672 0%, #248156 100%);
        padding: 20px;
        color: white;
        border-radius: 20px 20px 0 0;
    }

    .chatbot-header h4 {
        color: #fff!important;
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }

    .chatbot-header p {
        margin: 5px 0 0 0;
        font-size: 13px;
        opacity: 0.9;
    }

    /* Chatbot Messages */
    .chatbot-messages {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        background: #f8f9fa;
    }

    .chatbot-messages::-webkit-scrollbar {
        width: 6px;
    }

    .chatbot-messages::-webkit-scrollbar-thumb {
        background: #667eea;
        border-radius: 10px;
    }

    .message {
        margin-bottom: 15px;
        display: flex;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .message.bot {
        justify-content: flex-start;
    }

    .message.user {
        justify-content: flex-end;
    }

    .message-content {
        max-width: 75%;
        padding: 12px 16px;
        border-radius: 15px;
        font-size: 14px;
        line-height: 1.5;
    }

    .message.bot .message-content {
        background: white;
        color: #333;
        border-bottom-left-radius: 5px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .message.user .message-content {
        background: linear-gradient(135deg, #187672 0%, #248156 100%);
        color: white;
        border-bottom-right-radius: 5px;
    }

    /* Typing Indicator */
    .typing-indicator {
        display: none;
        padding: 10px 15px;
        background: white;
        border-radius: 15px;
        width: fit-content;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .typing-indicator.active {
        display: block;
    }

    .typing-indicator span {
        height: 8px;
        width: 8px;
        background: #667eea;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
        animation: typing 1.4s infinite;
    }

    .typing-indicator span:nth-child(2) {
        animation-delay: 0.2s;
    }

    .typing-indicator span:nth-child(3) {
        animation-delay: 0.4s;
    }

    @keyframes typing {
        0%, 60%, 100% {
            transform: translateY(0);
        }
        30% {
            transform: translateY(-10px);
        }
    }

    /* Quick Suggestions */
    .quick-suggestions {
        padding: 10px 20px;
        background: white;
        border-top: 1px solid #eee;
        display: flex;
        gap: 8px;
        overflow-x: auto;
    }

    .quick-suggestions::-webkit-scrollbar {
        height: 4px;
    }

    .quick-suggestions::-webkit-scrollbar-thumb {
        background: #667eea;
        border-radius: 10px;
    }

    .suggestion-btn {
        padding: 8px 15px;
        background: #f0f0f0;
        border: 1px solid #ddd;
        border-radius: 20px;
        font-size: 13px;
        white-space: nowrap;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .suggestion-btn:hover {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }

    /* Chatbot Input */
    .chatbot-input {
        padding: 15px 20px;
        background: white;
        border-top: 1px solid #eee;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .chatbot-input input {
        flex: 1;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 25px;
        font-size: 14px;
        outline: none;
        transition: all 0.3s ease;
    }

    .chatbot-input input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .chatbot-send-btn {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #187672 0%, #248156 100%);
        border: none;
        border-radius: 50%;
        color: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .chatbot-send-btn:hover {
        transform: scale(1.1);
    }

    .chatbot-send-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .chatbot-container {
            width: calc(100% - 40px);
            height: calc(100vh - 140px);
            right: 20px;
            bottom: 90px;
        }

        .chatbot-toggle {
            bottom: 20px;
            right: 20px;
        }
    }
</style>

<!-- Floating Chatbot HTML -->
<!-- Floating Chatbot HTML -->

<div class="chatbot-toggle" id="chatbotToggle">
    <i class="fa-regular fa-message"></i>
    <i class="fa-regular fa-xmark"></i>
</div>

<div class="chatbot-container" id="chatbotContainer">
    <!-- Header -->
    <div class="chatbot-header">
        <h4><i class="fa-solid fa-robot me-2 text-light fw-semibold"></i>AI Assistant</h4>
        <p class="text-light fw-semibold">আমি কিভাবে আপনাকে সাহায্য করতে পারি?</p>
    </div>

    <!-- Messages -->
    <div class="chatbot-messages" id="chatbotMessages">
        <!-- Dynamic initial greeting -->
        <div class="typing-indicator" id="typingIndicator">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>

    <!-- Quick Suggestions -->
    <!-- <div class="quick-suggestions">
        <button class="suggestion-btn" onclick="sendSuggestion('Total Employees কত?')">
            <i class="fa-regular fa-users me-1"></i>Employees
        </button>
        <button class="suggestion-btn" onclick="sendSuggestion('আজকের Sales কেমন?')">
            <i class="fa-regular fa-chart-line me-1"></i>Sales
        </button>
        <button class="suggestion-btn" onclick="sendSuggestion('Projects Status')">
            <i class="fa-regular fa-tasks me-1"></i>Projects
        </button>
    </div> -->
    <!-- Quick Suggestions -->
<div class="quick-suggestions">
    <button class="suggestion-btn" data-text="Total Employees কত?">
        <i class="fa-regular fa-users me-1"></i>Employees
    </button>
    <button class="suggestion-btn" data-text="আজকের Sales কেমন?">
        <i class="fa-regular fa-chart-line me-1"></i>Sales
    </button>
    <button class="suggestion-btn" data-text="Projects Status">
        <i class="fa-regular fa-tasks me-1"></i>Projects
    </button>
    <button class="suggestion-btn" data-text="Total CashIn কত?">
        <i class="fa-regular fa-tasks me-1"></i>Accounts Overview
    </button>
</div>


    <!-- Input -->
    <div class="chatbot-input">
        <input 
            type="text" 
            id="chatbotInput" 
            placeholder="আপনার প্রশ্ন লিখুন..."
            autocomplete="off"
        >
        <button class="chatbot-send-btn" id="chatbotSendBtn">
            <i class="fa-regular fa-paper-plane"></i>
        </button>
    </div>
</div>

<!-- Chatbot JavaScript -->
<script>

document.addEventListener('DOMContentLoaded', function(){
    const base_url = "<?= $GLOBALS['base_url'] ?>"; 
    const chatbotToggle = document.getElementById('chatbotToggle');
    const chatbotContainer = document.getElementById('chatbotContainer');
    const chatbotInput = document.getElementById('chatbotInput');
    const chatbotSendBtn = document.getElementById('chatbotSendBtn');
    const chatbotMessages = document.getElementById('chatbotMessages');
    const typingIndicator = document.getElementById('typingIndicator');

    // Toggle chatbot
    chatbotToggle.addEventListener('click', function(){
        chatbotToggle.classList.toggle('active');
        chatbotContainer.classList.toggle('active');
        if(chatbotContainer.classList.contains('active')){
            chatbotInput.focus();
        }
    });

    // Add message
    function addMessage(text, sender){
        const msgDiv = document.createElement('div');
        msgDiv.className = 'message ' + sender;
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        contentDiv.textContent = text;
        msgDiv.appendChild(contentDiv);
        chatbotMessages.insertBefore(msgDiv, typingIndicator);
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
    }

    // Initial greeting from backend
    fetch(base_url + '/ai-chatbox', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ message: 'init' })
    })
    .then(res => res.json())
    .then(res => addMessage(res.reply,'bot'))
    .catch(()=>addMessage('দুঃখিত, সার্ভারে সমস্যা হয়েছে।','bot'));

    // Send message
    function sendMessage(){
        const message = chatbotInput.value.trim();
        if(!message) return;

        addMessage(message,'user');
        chatbotInput.value='';
        typingIndicator.style.display='block';
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;

        fetch(base_url + '/ai-chatbox', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ message })
        })
        .then(res => res.json())
        .then(res => {
            typingIndicator.style.display='none';
            addMessage(res.reply,'bot');
        })
        .catch(()=> {
            typingIndicator.style.display='none';
            addMessage('দুঃখিত, সার্ভারে সমস্যা হয়েছে2।','bot');
        });
    }

    // Send on click / enter
    chatbotSendBtn.addEventListener('click', sendMessage);
    chatbotInput.addEventListener('keypress', function(e){
        if(e.key==='Enter') sendMessage();
    });

    // Quick suggestions
    document.querySelectorAll('.suggestion-btn').forEach(btn => {
        btn.addEventListener('click', function(){
            chatbotInput.value = this.getAttribute('data-text');
            sendMessage();
        });
    });
});

</script>

<script>
    // document.addEventListener('DOMContentLoaded', function() {
//     const chatbotToggle = document.getElementById('chatbotToggle');
//     const chatbotContainer = document.getElementById('chatbotContainer');
//     const chatbotInput = document.getElementById('chatbotInput');
//     const chatbotSendBtn = document.getElementById('chatbotSendBtn');
//     const chatbotMessages = document.getElementById('chatbotMessages');
//     const typingIndicator = document.getElementById('typingIndicator');

//     // Toggle chatbot
//     chatbotToggle.addEventListener('click', function() {
//         chatbotToggle.classList.toggle('active');
//         chatbotContainer.classList.toggle('active');
//         if (chatbotContainer.classList.contains('active')) {
//             chatbotInput.focus();
//         }
//     });

//     // Send message on button click
//     chatbotSendBtn.addEventListener('click', sendMessage);

//     // Send message on Enter key
//     chatbotInput.addEventListener('keypress', function(e) {
//         if (e.key === 'Enter') {
//             sendMessage();
//         }
//     });

//     function sendMessage() {
//         const message = chatbotInput.value.trim();
//         if (message === '') return;

//         // Add user message
//         addMessage(message, 'user');
//         chatbotInput.value = '';

//         // Show typing indicator
//         typingIndicator.classList.add('active');
//         scrollToBottom();

//         // Simulate AI response
//         setTimeout(() => {
//             typingIndicator.classList.remove('active');
//             const response = generateResponse(message);
//             addMessage(response, 'bot');
//         }, 1500);
//     }

//     function addMessage(text, sender) {
//         const messageDiv = document.createElement('div');
//         messageDiv.className = `message ${sender}`;
        
//         const contentDiv = document.createElement('div');
//         contentDiv.className = 'message-content';
//         contentDiv.textContent = text;
        
//         messageDiv.appendChild(contentDiv);
//         chatbotMessages.insertBefore(messageDiv, typingIndicator);
        
//         scrollToBottom();
//     }

//     function scrollToBottom() {
//         chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
//     }

//     function generateResponse(message) {
//         const lowerMessage = message.toLowerCase();

//         // Dashboard stats responses
//         if (lowerMessage.includes('employee') || lowerMessage.includes('কর্মচারী')) {
//             return 'আপনার ড্যাশবোর্ডে Total Employees এর সংখ্যা দেখানো আছে। বিস্তারিত জানতে উপরের card দেখুন।';
//         }
//         if (lowerMessage.includes('client') || lowerMessage.includes('ক্লায়েন্ট')) {
//             return 'Total Clients এবং তাদের পরিবর্তনের হার ড্যাশবোর্ডের দ্বিতীয় card-এ পাওয়া যাবে।';
//         }
//         if (lowerMessage.includes('project') || lowerMessage.includes('প্রজেক্ট')) {
//             return 'আপনার সকল প্রজেক্টের তথ্য এবং Project wise Flat Sale চার্টে দেখতে পাবেন।';
//         }
//         if (lowerMessage.includes('sales') || lowerMessage.includes('বিক্রয়')) {
//             return 'Sales Overview চার্টে আপনি সাপ্তাহিক, মাসিক এবং বার্ষিক sales দেখতে পারবেন।';
//         }
//         if (lowerMessage.includes('cash') || lowerMessage.includes('টাকা')) {
//             return 'Cash In, Cash Out এবং Net Balance এর সম্পূর্ণ তথ্য ড্যাশবোর্ডে দেখানো আছে। ৳ চিহ্ন সহ cards গুলো দেখুন।';
//         }
//         if (lowerMessage.includes('booking') || lowerMessage.includes('বুকিং')) {
//             return 'Total Flat Booking এবং Booking Money এর তথ্য আলাদা cards এ দেখানো আছে।';
//         }
//         if (lowerMessage.includes('payment') || lowerMessage.includes('পেমেন্ট')) {
//             return 'Client Payments, Management Cash In, এবং Registration Money - সব তথ্য ড্যাশবোর্ডে available আছে।';
//         }
//         if (lowerMessage.includes('task') || lowerMessage.includes('টাস্ক')) {
//             return 'Complete Tasks এর সংখ্যা ড্যাশবোর্ডে দেখানো আছে।';
//         }

//         // General responses
//         if (lowerMessage.includes('help') || lowerMessage.includes('সাহায্য')) {
//             return 'আমি আপনার dashboard সম্পর্কে যেকোনো প্রশ্নের উত্তর দিতে পারি। Employees, Clients, Projects, Sales, Cash Flow - যেকোনো বিষয়ে জিজ্ঞাসা করুন!';
//         }
//         if (lowerMessage.includes('hello') || lowerMessage.includes('hi') || lowerMessage.includes('হাই') || lowerMessage.includes('হ্যালো')) {
//             return 'হ্যালো! আমি আপনাকে কিভাবে সাহায্য করতে পারি? 😊';
//         }
//         if (lowerMessage.includes('thanks') || lowerMessage.includes('ধন্যবাদ') || lowerMessage.includes('thank you')) {
//             return 'আপনাকে স্বাগতম! আরও কিছু জানতে চাইলে জিজ্ঞাসা করুন। 🙏';
//         }

//         // Default response
//         return 'আপনার প্রশ্নটি আমি ঠিকমত বুঝতে পারিনি। আপনি dashboard এর যেকোনো অংশ সম্পর্কে বিস্তারিত জানতে পারেন। যেমন: Employees, Clients, Projects, Sales, Cash Flow ইত্যাদি।';
//     }

//     // Quick suggestion function
//     window.sendSuggestion = function(text) {
//         chatbotInput.value = text;
//         sendMessage();
//     };
// });
</script>