class MentionsAutocomplete {
    constructor() {
        this.init();
    }

    init() {
        // Wait for RichEditor to be loaded
        document.addEventListener("DOMContentLoaded", () => {
            this.setupMentions();
        });

        // Also setup on Livewire updates
        document.addEventListener("livewire:load", () => {
            this.setupMentions();
        });

        document.addEventListener("livewire:update", () => {
            this.setupMentions();
        });
    }

    setupMentions() {
        const richEditors = document.querySelectorAll(
            '[data-enable-mentions="true"]'
        );

        richEditors.forEach((editor) => {
            if (editor.dataset.mentionsInitialized) return;

            // Fix: Add error handling for JSON parsing
            let usersData = [];
            try {
                const rawData = editor.dataset.users || "[]";
                console.log("Raw users data:", rawData); // Debug log
                usersData = JSON.parse(rawData);
                console.log("Parsed users data:", usersData); // Debug log
            } catch (error) {
                console.error("Error parsing users data:", error);
                console.log("Failed to parse:", editor.dataset.users);
                usersData = [];
            }

            this.initMentionsForEditor(editor, usersData);
            editor.dataset.mentionsInitialized = "true";
        });
    }

    initMentionsForEditor(editorElement, users) {
        // Find the actual contenteditable element (varies by RichEditor implementation)
        const contentEditable =
            editorElement.querySelector('[contenteditable="true"]') ||
            editorElement.querySelector(".ProseMirror") ||
            editorElement.querySelector(".ql-editor") || // Added for Quill editor
            editorElement.querySelector("iframe")?.contentDocument?.body;

        if (!contentEditable) {
            console.warn("Could not find contenteditable element");
            console.log("Editor element:", editorElement); // Debug log
            return;
        }

        console.log("Found contenteditable element:", contentEditable); // Debug log
        console.log("Users for mentions:", users); // Debug log

        this.createMentionsDropdown();
        this.attachMentionsListener(contentEditable, users);
    }

    createMentionsDropdown() {
        if (document.getElementById("mentions-dropdown")) return;

        const dropdown = document.createElement("div");
        dropdown.id = "mentions-dropdown";
        dropdown.className = "mentions-dropdown";
        dropdown.style.cssText = `
            position: absolute;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        `;
        document.body.appendChild(dropdown);
    }

    attachMentionsListener(element, users) {
        // Validate users array
        if (!Array.isArray(users)) {
            console.warn("Users is not an array:", users);
            return;
        }

        let mentionQuery = "";
        let mentionStart = -1;

        element.addEventListener("input", (e) => {
            const cursorPos = this.getCursorPosition(element);
            const text = element.textContent || element.innerText || "";

            // Find @ symbol before cursor
            let atIndex = -1;
            for (let i = cursorPos - 1; i >= 0; i--) {
                if (text[i] === "@") {
                    atIndex = i;
                    break;
                }
                if (text[i] === " " || text[i] === "\n") {
                    break;
                }
            }

            if (atIndex !== -1) {
                mentionStart = atIndex;
                mentionQuery = text.substring(atIndex + 1, cursorPos);
                this.showMentionsSuggestions(
                    element,
                    users,
                    mentionQuery,
                    mentionStart
                );
            } else {
                this.hideMentionsSuggestions();
                mentionStart = -1;
                mentionQuery = "";
            }
        });

        element.addEventListener("keydown", (e) => {
            const dropdown = document.getElementById("mentions-dropdown");
            if (dropdown && dropdown.style.display === "block") {
                const selected = dropdown.querySelector(
                    ".mention-item.selected"
                );

                if (e.key === "ArrowDown") {
                    e.preventDefault();
                    this.selectNextMention();
                } else if (e.key === "ArrowUp") {
                    e.preventDefault();
                    this.selectPrevMention();
                } else if (e.key === "Enter" || e.key === "Tab") {
                    e.preventDefault();
                    if (selected) {
                        this.insertMention(
                            element,
                            selected.dataset,
                            mentionStart
                        );
                    }
                } else if (e.key === "Escape") {
                    this.hideMentionsSuggestions();
                }
            }
        });
    }

    showMentionsSuggestions(element, users, query, mentionStart) {
        const dropdown = document.getElementById("mentions-dropdown");
        if (!dropdown) return;

        // Filter users with better error handling
        const filteredUsers = users
            .filter((user) => {
                // Ensure user object has required properties
                if (!user || typeof user !== "object") return false;
                if (!user.username || !user.name) return false;

                return (
                    user.username.toLowerCase().includes(query.toLowerCase()) ||
                    user.name.toLowerCase().includes(query.toLowerCase())
                );
            })
            .slice(0, 5);

        if (filteredUsers.length === 0) {
            this.hideMentionsSuggestions();
            return;
        }

        dropdown.innerHTML = filteredUsers
            .map(
                (user, index) => `
            <div class="mention-item ${index === 0 ? "selected" : ""}"
                 data-username="${user.username || ""}"
                 data-name="${user.name || ""}"
                 data-id="${user.id || ""}"
                 style="
                     padding: 8px 12px;
                     cursor: pointer;
                     display: flex;
                     align-items: center;
                     gap: 8px;
                     background: ${index === 0 ? "#f3f4f6" : "transparent"};
                 ">
                <img src="${user.avatar || "/default-avatar.png"}" alt="${
                    user.name || "User"
                }" style="
                    width: 24px;
                    height: 24px;
                    border-radius: 50%;
                    object-fit: cover;
                " onerror="this.src='/default-avatar.png'">
                <div>
                    <div style="font-weight: 500; font-size: 14px;">${
                        user.name || "Unknown User"
                    }</div>
                    <div style="color: #6b7280; font-size: 12px;">@${
                        user.username || "username"
                    }</div>
                </div>
            </div>
        `
            )
            .join("");

        // Position dropdown
        const rect = element.getBoundingClientRect();
        dropdown.style.left = rect.left + "px";
        dropdown.style.top = rect.bottom + 5 + "px";
        dropdown.style.display = "block";

        // Add click listeners
        dropdown.querySelectorAll(".mention-item").forEach((item) => {
            item.addEventListener("click", () => {
                this.insertMention(element, item.dataset, mentionStart);
            });

            item.addEventListener("mouseenter", () => {
                dropdown
                    .querySelectorAll(".mention-item")
                    .forEach((i) => i.classList.remove("selected"));
                item.classList.add("selected");
            });
        });
    }

    hideMentionsSuggestions() {
        const dropdown = document.getElementById("mentions-dropdown");
        if (dropdown) {
            dropdown.style.display = "none";
        }
    }

    selectNextMention() {
        const dropdown = document.getElementById("mentions-dropdown");
        if (!dropdown) return;

        const items = dropdown.querySelectorAll(".mention-item");
        const selected = dropdown.querySelector(".mention-item.selected");
        const currentIndex = Array.from(items).indexOf(selected);
        const nextIndex = (currentIndex + 1) % items.length;

        items.forEach((item) => item.classList.remove("selected"));
        items[nextIndex].classList.add("selected");
        items[nextIndex].style.background = "#f3f4f6";
        items.forEach((item, i) => {
            if (i !== nextIndex) item.style.background = "transparent";
        });
    }

    selectPrevMention() {
        const dropdown = document.getElementById("mentions-dropdown");
        if (!dropdown) return;

        const items = dropdown.querySelectorAll(".mention-item");
        const selected = dropdown.querySelector(".mention-item.selected");
        const currentIndex = Array.from(items).indexOf(selected);
        const prevIndex =
            currentIndex === 0 ? items.length - 1 : currentIndex - 1;

        items.forEach((item) => item.classList.remove("selected"));
        items[prevIndex].classList.add("selected");
        items[prevIndex].style.background = "#f3f4f6";
        items.forEach((item, i) => {
            if (i !== prevIndex) item.style.background = "transparent";
        });
    }

    insertMention(element, userData, mentionStart) {
        const text = element.textContent || element.innerText || "";
        const cursorPos = this.getCursorPosition(element);

        // Replace the @query with @username
        const beforeMention = text.substring(0, mentionStart);
        const afterMention = text.substring(cursorPos);
        const mentionText = `@${userData.username || "user"} `;

        const newText = beforeMention + mentionText + afterMention;

        if (element.textContent !== undefined) {
            element.textContent = newText;
        } else {
            element.innerText = newText;
        }

        // Set cursor position after mention
        const newCursorPos = mentionStart + mentionText.length;
        this.setCursorPosition(element, newCursorPos);

        this.hideMentionsSuggestions();

        // Trigger input event to update the form
        element.dispatchEvent(new Event("input", { bubbles: true }));
    }

    getCursorPosition(element) {
        const selection = window.getSelection();
        if (selection.rangeCount === 0) return 0;

        const range = selection.getRangeAt(0);
        const preCaretRange = range.cloneRange();
        preCaretRange.selectNodeContents(element);
        preCaretRange.setEnd(range.endContainer, range.endOffset);
        return preCaretRange.toString().length;
    }

    setCursorPosition(element, position) {
        const range = document.createRange();
        const selection = window.getSelection();

        try {
            range.setStart(element.firstChild || element, position);
            range.setEnd(element.firstChild || element, position);
            selection.removeAllRanges();
            selection.addRange(range);
        } catch (e) {
            console.warn("Could not set cursor position:", e);
        }
    }
}

// Initialize when script loads
new MentionsAutocomplete();
