class ChatApp {
    constructor() {
        this.roles = window.CHARACTERS || [];  // 从页面获取角色数据
        this.apiKeys = window.API_KEYS || [];  // 从页面获取API Keys数据
        this.chats = [];
        this.currentChat = null;
        this.chatIdCounter = 1;
        this.isTyping = false;
        this.currentController = null;
        this.selectedApiKey = null;
        this.selectedModel = null;

        this.init();
    }

    async init() {
        await this.loadConversations();
        this.bindEvents();
        this.initMarkdown();
        this.initMermaid();
        this.initApiKeySelect();
    }

    async loadConversations() {
        try {
            const response = await fetch('/open-ai/chat/conversations');
            if (!response.ok) {
                throw new Error('Failed to load conversations');
            }
            const conversations = await response.json();
            this.chats = conversations.map(conv => ({
                id: conv.id,
                title: conv.title,
                role: conv.actor.id,
                avatar: conv.actor.avatar,
                messages: []  // 初始化为空数组，等点击时再加载
            }));
            this.updateChatList();
        } catch (error) {
            console.error('Error loading conversations:', error);
        }
    }

    async loadConversationDetails(conversationId) {
        try {
            const response = await fetch(`/open-ai/chat/conversation/${conversationId}`);
            if (!response.ok) {
                throw new Error('Failed to load conversation details');
            }
            const conversation = await response.json();
            // 更新本地会话数据
            const chat = this.chats.find(c => c.id === conversationId);
            if (chat) {
                chat.messages = conversation.messages.map(msg => ({
                    role: msg.role,
                    content: msg.content,
                    model: msg.model,
                    usage: msg.usage
                }));
            }
            return chat;
        } catch (error) {
            console.error('Error loading conversation details:', error);
            return null;
        }
    }

    bindEvents() {
        // 新建对话按钮
        document.querySelector('.new-chat-btn button').addEventListener('click', () => {
            this.showRoleSelect();
        });

        // 角色选择事件已经在模板中通过 data-role-id 绑定
        document.querySelectorAll('.role-card').forEach(card => {
            card.addEventListener('click', (e) => {
                const roleId = e.currentTarget.dataset.roleId;
                const role = this.roles.find(r => r.id === roleId);
                if (role) {
                    this.startNewChat(role);
                }
            });
        });

        // 聊天列表点击事件
        this.bindChatListEvents();

        // 消息发送
        const form = document.querySelector('.input-box');
        const textarea = form.querySelector('textarea');

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.sendMessage();
        });

        textarea.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        textarea.addEventListener('input', (e) => {
            e.target.style.height = '24px';
            e.target.style.height = e.target.scrollHeight + 'px';
        });
    }

    bindChatListEvents() {
        const chatList = document.querySelector('.chat-list');
        chatList.querySelectorAll('.chat-item').forEach(item => {
            item.addEventListener('click', () => {
                const chatId = item.dataset.chatId;
                const chat = this.chats.find(c => c.id === chatId);
                if (chat) {
                    this.selectChat(chat);
                }
            });
        });
    }

    initMarkdown() {
        marked.setOptions({
            highlight: (code, lang) => {
                if (lang && hljs.getLanguage(lang)) {
                    return hljs.highlight(code, { language: lang }).value;
                }
                return hljs.highlightAuto(code).value;
            },
            breaks: true
        });
    }

    initMermaid() {
        mermaid.initialize({
            startOnLoad: false,
            theme: 'default',
            securityLevel: 'loose'
        });
    }

    initApiKeySelect() {
        const apiKeySelect = document.querySelector('.api-key-select');

        if (this.apiKeys.length > 0) {
            // 设置初始选中的API Key和模型
            const firstKey = this.apiKeys[0];
            this.selectedApiKey = firstKey.id;
            this.selectedModel = firstKey.model;
        }

        // 监听API Key选择变化
        apiKeySelect.addEventListener('change', (e) => {
            const selectedKey = this.apiKeys.find(k => k.id === e.target.value);
            if (selectedKey) {
                this.selectedApiKey = selectedKey.id;
                this.selectedModel = selectedKey.model;
            }
        });
    }

    renderMarkdown() {
        document.querySelectorAll('.message-content').forEach(el => {
            if (!el.hasAttribute('data-rendered')) {
                const content = el.textContent;
                el.setAttribute('data-original', content);
                el.innerHTML = marked.parse(content);

                // 添加复制按钮
                const actions = document.createElement('div');
                actions.className = 'message-actions';
                actions.innerHTML = `
                    <button class="context-pin" title="固定上下文">
                        <i class="ri-pushpin-line"></i>
                    </button>
                    <button class="context-ignore" title="忽略上下文">
                        <i class="ri-forbid-line"></i>
                    </button>
                    <button class="copy-markdown" title="复制 Markdown">
                        <i class="ri-file-copy-line"></i>
                    </button>
                `;
                el.appendChild(actions);

                // 绑定复制事件
                actions.querySelector('.copy-markdown').addEventListener('click', () => {
                    navigator.clipboard.writeText(content).then(() => {
                        const btn = actions.querySelector('.copy-markdown');
                        const originalText = btn.innerHTML;
                        btn.innerHTML = '<i class="ri-check-line"></i>已复制';
                        setTimeout(() => {
                            btn.innerHTML = originalText;
                        }, 2000);
                    });
                });

                // 重新渲染 mermaid 图表
                el.querySelectorAll('pre > code.language-mermaid').forEach(diagram => {
                    const pre = diagram.parentElement;
                    const wrapper = document.createElement('div');
                    wrapper.className = 'mermaid-wrapper';

                    const div = document.createElement('div');
                    div.className = 'mermaid';
                    div.textContent = diagram.textContent;

                    const downloadBtn = document.createElement('button');
                    downloadBtn.className = 'mermaid-download';
                    downloadBtn.innerHTML = '<i class="ri-download-line"></i>';
                    downloadBtn.title = '下载为PNG';

                    wrapper.appendChild(div);
                    wrapper.appendChild(downloadBtn);
                    pre.parentElement.replaceChild(wrapper, pre);

                    // 绑定下载事件
                    downloadBtn.addEventListener('click', () => {
                        const svg = wrapper.querySelector('svg');
                        if (svg) {
                            // 获取原始尺寸和视图框
                            const box = svg.getBoundingClientRect();
                            const originalViewBox = svg.getAttribute('viewBox');
                            const scale = 5;

                            // 创建一个临时的 SVG 元素，设置更大的尺寸
                            const svgData = new XMLSerializer().serializeToString(svg);
                            const tempSvg = document.createElement('div');
                            tempSvg.innerHTML = svgData;
                            const newSvg = tempSvg.firstChild;

                            // 保持原始视图框以确保完整性
                            if (originalViewBox) {
                                newSvg.setAttribute('viewBox', originalViewBox);
                            }

                            // 设置更高的 SVG 质量
                            const width = box.width * scale;
                            const height = box.height * scale;
                            newSvg.setAttribute('width', width);
                            newSvg.setAttribute('height', height);

                            // 优化所有文本和线条
                            newSvg.querySelectorAll('text').forEach(text => {
                                const fontSize = parseFloat(getComputedStyle(text).fontSize);
                                text.style.fontSize = (fontSize * scale / box.width * width) + 'px';
                            });

                            newSvg.querySelectorAll('path, line').forEach(path => {
                                const strokeWidth = parseFloat(getComputedStyle(path).strokeWidth);
                                if (strokeWidth) {
                                    path.style.strokeWidth = (strokeWidth * scale / box.width * width) + 'px';
                                }
                            });

                            const canvas = document.createElement('canvas');
                            const ctx = canvas.getContext('2d');

                            // 设置 canvas 尺寸
                            canvas.width = width;
                            canvas.height = height;

                            // 确保图像渲染质量
                            ctx.imageSmoothingEnabled = true;
                            ctx.imageSmoothingQuality = 'high';

                            const newSvgData = new XMLSerializer().serializeToString(newSvg);
                            const img = new Image();

                            img.onload = function() {
                                // 绘制白色背景
                                ctx.fillStyle = 'white';
                                ctx.fillRect(0, 0, width, height);

                                // 绘制图像
                                ctx.drawImage(img, 0, 0, width, height);

                                // 使用最高质量导出 PNG
                                canvas.toBlob(function(blob) {
                                    const url = URL.createObjectURL(blob);
                                    const a = document.createElement('a');
                                    a.href = url;
                                    a.download = 'mermaid-diagram.png';
                                    a.click();
                                    URL.revokeObjectURL(url);
                                }, 'image/png', 1.0);
                            };

                            img.src = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(newSvgData)));
                        }
                    });
                });

                el.setAttribute('data-rendered', 'true');
            }
        });

        // 重新初始化所有 mermaid 图表
        mermaid.init();
    }

    showRoleSelect() {
        document.querySelector('.welcome-screen').style.display = 'flex';
        document.querySelector('.chat-messages').style.display = 'none';
        document.querySelector('.input-area').style.display = 'none';
    }

    async startNewChat(role) {
        try {
            const response = await fetch('/open-ai/chat/create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    characterId: role.id,
                    title: `${role.name} #${this.chatIdCounter}`
                })
            });

            if (!response.ok) {
                throw new Error('Failed to create conversation');
            }

            const conversation = await response.json();
            const chat = {
                id: conversation.id,
                title: conversation.title,
                role: role.id,
                avatar: role.avatar,
                messages: conversation.messages.map(msg => ({
                    role: msg.role,
                    content: msg.content,
                    model: msg.model,
                    usage: msg.usage
                }))
            };

            this.chats.unshift(chat);
            this.selectChat(chat);
            this.updateChatList();
        } catch (error) {
            console.error('Error creating conversation:', error);
        }
    }

    async selectChat(chat) {
        // 先显示加载中状态
        document.querySelector('.welcome-screen').style.display = 'none';
        document.querySelector('.chat-messages').style.display = 'block';
        document.querySelector('.input-area').style.display = 'flex';
        document.querySelector('.chat-messages').innerHTML = '<div class="loading">加载中...</div>';

        try {
            // 加载会话详情
            const updatedChat = await this.loadConversationDetails(chat.id);
            if (updatedChat) {
                this.currentChat = updatedChat;
            } else {
                this.currentChat = chat;
                document.querySelector('.chat-messages').innerHTML = '<div class="error">加载失败</div>';
                return;
            }

            // 更新活动状态
            document.querySelectorAll('.chat-item').forEach(item => {
                item.classList.toggle('active', item.dataset.chatId === chat.id);
            });

            this.renderMessages();
            this.scrollToBottom();
        } catch (error) {
            console.error('Error selecting chat:', error);
            document.querySelector('.chat-messages').innerHTML = '<div class="error">加载失败</div>';
        }
    }

    updateChatList() {
        const chatList = document.querySelector('.chat-list');
        chatList.innerHTML = this.chats.map(chat => `
            <div class="chat-item ${this.currentChat?.id === chat.id ? 'active' : ''}" 
                 data-chat-id="${chat.id}">
                <div class="role-icon">
                    <img src="${chat.avatar}" alt="${chat.title}">
                </div>
                <div class="title">${chat.title}</div>
            </div>
        `).join('');

        this.bindChatListEvents();
    }

    renderMessages() {
        const messagesContainer = document.querySelector('.chat-messages');
        messagesContainer.innerHTML = this.currentChat.messages.map(message => `
            <div class="message ${message.role}">
                <div class="message-wrapper">
                    <div class="avatar">
                        <img src="${message.role === 'user' ? 'https://api.dicebear.com/7.x/avataaars/svg?seed=user' : this.currentChat.avatar}" 
                             alt="${message.role}">
                    </div>
                    <div class="message-content">${message.content}</div>
                </div>
                ${message.role === 'assistant' && message.model ? `
                    <div class="message-info">
                        <span class="model">${message.model}</span>
                        ${message.usage ? `
                            <span class="usage">
                                输入: ${message.usage.prompt_tokens} · 
                                输出: ${message.usage.completion_tokens} · 
                                总计: ${message.usage.total_tokens}
                            </span>
                        ` : ''}
                    </div>
                ` : ''}
            </div>
        `).join('');

        this.renderMarkdown();
    }

    async sendMessage() {
        const textarea = document.querySelector('.input-box textarea');
        const message = textarea.value.trim();

        if (!message || !this.currentChat || !this.selectedApiKey) return;

        // 如果有正在进行的请求，中断它并保存内容
        if (this.currentController) {
            this.currentController.abort();
            // 等待前一个请求的清理完成
            await new Promise(resolve => setTimeout(resolve, 100));
        }

        // 创建新的 AbortController
        this.currentController = new AbortController();

        const userMessage = {
            role: 'user',
            content: message
        };

        this.currentChat.messages.push(userMessage);
        textarea.value = '';
        textarea.style.height = '24px';

        this.renderMessages();
        this.scrollToBottom();

        let content = '';
        let typingIndicator = null;

        const saveContent = () => {
            if (content.trim()) {
                const aiMessage = {
                    role: 'assistant',
                    content: content,
                    model: this.selectedModel,
                    usage: {
                        prompt_tokens: 0,
                        completion_tokens: 0,
                        total_tokens: 0
                    }
                };
                this.currentChat.messages.push(aiMessage);
                this.renderMessages();
                this.scrollToBottom();
            }
        };

        try {
            // 添加"正在输入"提示
            typingIndicator = document.createElement('div');
            typingIndicator.className = 'typing-indicator';
            typingIndicator.innerHTML = `
                <div class="message assistant">
                    <div class="avatar">
                        <img src="${this.currentChat.avatar}" alt="assistant">
                    </div>
                    <div class="dots">
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                    </div>
                </div>
            `;
            document.querySelector('.chat-messages').appendChild(typingIndicator);
            this.scrollToBottom();

            const response = await fetch('/open-ai/chat/reply', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    characterId: this.currentChat.role,
                    conversationId: this.currentChat.id,
                    message: userMessage.content,
                    apiKeyId: this.selectedApiKey,
                    model: this.selectedModel
                }),
                signal: this.currentController.signal
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();

            while (true) {
                const {value, done} = await reader.read();
                if (done) break;

                const text = decoder.decode(value);
                const lines = text.split('\n');

                for (const line of lines) {
                    if (line.startsWith('data: ')) {
                        try {
                            const jsonData = line.slice(6).trim();
                            if (!jsonData) continue;  // Skip empty data lines

                            const data = JSON.parse(jsonData);
                            if (data.error) {
                                throw new Error(data.error);
                            }
                            if (data.done) {
                                // 移除"正在输入"提示
                                if (typingIndicator) {
                                    typingIndicator.remove();
                                }
                                saveContent();
                                return;
                            } else {
                                content += data.content;
                                // 实时更新"正在输入"的内容
                                const dotsElement = typingIndicator?.querySelector('.dots');
                                if (dotsElement) {
                                    dotsElement.textContent = content;
                                }
                            }
                        } catch (e) {
                            console.error('Error parsing SSE data:', e);
                        }
                    }
                }
            }
        } catch (error) {
            if (error.name === 'AbortError') {
                console.log('Request aborted, saving partial content');
                saveContent();
            } else {
                console.error('Error:', error);
            }
            typingIndicator?.remove();
        } finally {
            this.currentController = null;  // 清理 controller
        }
    }

    scrollToBottom() {
        const container = document.querySelector('.chat-messages');
        container.scrollTop = container.scrollHeight;
    }
}

// 初始化应用
document.addEventListener('DOMContentLoaded', () => {
    window.chatApp = new ChatApp();
});
