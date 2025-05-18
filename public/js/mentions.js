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

            // Enhanced error handling for JSON parsing
            let usersData = [];
            try {
                const rawData = editor.dataset.users || "[]";
                console.log("Raw users data:", rawData); // Debug log

                // Check if rawData is empty or not valid JSON
                if (!rawData || rawData.trim() === "") {
                    console.warn("Empty users data, using empty array");
                    usersData = [];
                } else {
                    // Try to parse the JSON
                    usersData = JSON.parse(rawData);

                    // Ensure it's an array
                    if (!Array.isArray(usersData)) {
                        console.warn(
                            "Users data is not an array, converting:",
                            usersData
                        );
                        usersData = Array.isArray(usersData) ? usersData : [];
                    }

                    console.log("Parsed users data:", usersData); // Debug log
                }
            } catch (error) {
                console.error("Error parsing users data:", error);
                console.log("Failed to parse raw data:", editor.dataset.users);
                usersData = [];
            }

            // Validate users array structure
            usersData = this.validateUsersData(usersData);

            this.initMentionsForEditor(editor, usersData);
            editor.dataset.mentionsInitialized = "true";
        });
    }

    // New method to validate and clean users data
    validateUsersData(users) {
        if (!Array.isArray(users)) {
            console.warn("Users data is not an array");
            return [];
        }

        return users
            .filter((user) => {
                // Ensure user is an object with required properties
                if (!user || typeof user !== "object") {
                    console.warn("Invalid user object:", user);
                    return false;
                }

                // Ensure required fields exist
                if (!user.id || !user.username || !user.name) {
                    console.warn("User missing required fields:", user);
                    return false;
                }

                return true;
            })
            .map((user) => ({
                id: user.id,
                username: user.username,
                name: user.name,
                avatar: user.avatar || "/default-avatar.png",
            }));
    }

    initMentionsForEditor(editorElement, users) {
        // Find the actual contenteditable element
        const contentEditable = this.findContentEditableElement(editorElement);

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

    // Enhanced method to find contenteditable element
    findContentEditableElement(editorElement) {
        // Try common selectors for different rich editor implementations
        const selectors = [
            '[contenteditable="true"]',
            ".ProseMirror",
            ".ql-editor",
            ".trix-content",
            ".fr-element",
            "textarea",
        ];

        for (const selector of selectors) {
            const element = editorElement.querySelector(selector);
            if (element) {
                return element;
            }
        }

        // Try to find iframe for some rich editors
        const iframe = editorElement.querySelector("iframe");
        if (iframe && iframe.contentDocument) {
            return iframe.contentDocument.body;
        }

        // If all else fails, check if the element itself is contenteditable
        if (
            editorElement.isContentEditable ||
            editorElement.contentEditable === "true"
        ) {
            return editorElement;
        }

        return null;
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
            min-width: 200px;
        `;
        document.body.appendChild(dropdown);
    }

    attachMentionsListener(element, users) {
        // Validate users array again
        if (!Array.isArray(users) || users.length === 0) {
            console.warn("No valid users available for mentions");
            return;
        }

        let mentionQuery = "";
        let mentionStart = -1;

        element.addEventListener("input", (e) => {
            const cursorPos = this.getCursorPosition(element);
            const text = this.getElementText(element);

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

        // Close dropdown when clicking outside
        document.addEventListener("click", (e) => {
            const dropdown = document.getElementById("mentions-dropdown");
            if (
                dropdown &&
                !dropdown.contains(e.target) &&
                !element.contains(e.target)
            ) {
                this.hideMentionsSuggestions();
            }
        });
    }

    // Helper method to get text from different element types
    getElementText(element) {
        if (element.tagName === "TEXTAREA") {
            return element.value;
        }
        return element.textContent || element.innerText || "";
    }

    showMentionsSuggestions(element, users, query, mentionStart) {
        const dropdown = document.getElementById("mentions-dropdown");
        if (!dropdown) return;

        // Filter users with better error handling
        const filteredUsers = users
            .filter((user) => {
                try {
                    // Ensure user object has required properties
                    if (!user || typeof user !== "object") return false;
                    if (!user.username || !user.name) return false;

                    const lowercaseQuery = query.toLowerCase();
                    return (
                        user.username.toLowerCase().includes(lowercaseQuery) ||
                        user.name.toLowerCase().includes(lowercaseQuery)
                    );
                } catch (error) {
                    console.warn("Error filtering user:", user, error);
                    return false;
                }
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
                 data-username="${this.escapeHtml(user.username)}"
                 data-name="${this.escapeHtml(user.name)}"
                 data-id="${user.id}"
                 style="
                     padding: 8px 12px;
                     cursor: pointer;
                     display: flex;
                     align-items: center;
                     gap: 8px;
                     background: ${index === 0 ? "#f3f4f6" : "transparent"};
                     border-bottom: 1px solid #f3f4f6;
                 "
                 onmouseenter="this.style.background='#f3f4f6'"
                 onmouseleave="this.style.background='${
                     index === 0 ? "#f3f4f6" : "transparent"
                 }'">
                <img src="${user.avatar}" alt="${this.escapeHtml(
                    user.name
                )}" style="
                    width: 32px;
                    height: 32px;
                    border-radius: 50%;
                    object-fit: cover;
                    background: #f3f4f6;
                " onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(
                    user.name
                )}&size=32&background=random'">
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: 500; font-size: 14px; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${this.escapeHtml(
                        user.name
                    )}</div>
                    <div style="color: #6b7280; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">@${this.escapeHtml(
                        user.username
                    )}</div>
                </div>
            </div>
        `
            )
            .join("");

        // Position dropdown
        const rect = element.getBoundingClientRect();
        const dropdownRect = dropdown.getBoundingClientRect();

        // Calculate position
        let left = rect.left;
        let top = rect.bottom + 5;

        // Adjust if dropdown would go off screen
        if (left + 200 > window.innerWidth) {
            left = window.innerWidth - 210;
        }

        if (top + 200 > window.innerHeight) {
            top = rect.top - 205;
        }

        dropdown.style.left = left + "px";
        dropdown.style.top = top + "px";
        dropdown.style.display = "block";

        // Add click listeners
        dropdown.querySelectorAll(".mention-item").forEach((item) => {
            item.addEventListener("click", (e) => {
                e.preventDefault();
                e.stopPropagation();
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

    // Helper method to escape HTML
    escapeHtml(text) {
        const div = document.createElement("div");
        div.textContent = text;
        return div.innerHTML;
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
    }

    insertMention(element, userData, mentionStart) {
        try {
            const isTextarea = element.tagName === "TEXTAREA";
            const text = this.getElementText(element);
            const cursorPos = this.getCursorPosition(element);

            // Replace the @query with @username
            const beforeMention = text.substring(0, mentionStart);
            const afterMention = text.substring(cursorPos);
            const mentionText = `@${userData.username} `;

            const newText = beforeMention + mentionText + afterMention;

            if (isTextarea) {
                element.value = newText;
                // Set cursor position for textarea
                const newCursorPos = mentionStart + mentionText.length;
                element.setSelectionRange(newCursorPos, newCursorPos);
            } else {
                if (element.textContent !== undefined) {
                    element.textContent = newText;
                } else {
                    element.innerText = newText;
                }
                // Set cursor position for contenteditable
                const newCursorPos = mentionStart + mentionText.length;
                this.setCursorPosition(element, newCursorPos);
            }

            this.hideMentionsSuggestions();

            // Trigger input event to update the form
            element.dispatchEvent(new Event("input", { bubbles: true }));

            // Focus back to element
            element.focus();
        } catch (error) {
            console.error("Error inserting mention:", error);
            this.hideMentionsSuggestions();
        }
    }

    getCursorPosition(element) {
        try {
            if (element.tagName === "TEXTAREA") {
                return element.selectionStart;
            }

            const selection = window.getSelection();
            if (selection.rangeCount === 0) return 0;

            const range = selection.getRangeAt(0);
            const preCaretRange = range.cloneRange();
            preCaretRange.selectNodeContents(element);
            preCaretRange.setEnd(range.endContainer, range.endOffset);
            return preCaretRange.toString().length;
        } catch (error) {
            console.warn("Error getting cursor position:", error);
            return 0;
        }
    }

    setCursorPosition(element, position) {
        try {
            if (element.tagName === "TEXTAREA") {
                element.setSelectionRange(position, position);
                return;
            }

            const range = document.createRange();
            const selection = window.getSelection();

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
