(() => {
  const form = document.querySelector("#chat-form");
  const input = document.querySelector("#question");
  const list = document.querySelector("#conversation");
  const welcome = document.querySelector("#welcome");
  const sendButton = document.querySelector("#send-button");
  const count = document.querySelector("#char-count");
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  let busy = false;
  let maintenanceMode = false;
  let historyReady = Promise.resolve();

  const scrollToBottom = () => {
    list.scrollTop = list.scrollHeight;
  };
  const appendLinkedText = (parent, text) => {
    const expression = /\*\*[^*\n]+\*\*|__[^_\n]+__|https?:\/\/[^\s<>"']+/gi;
    let cursor = 0;
    for (const match of text.matchAll(expression)) {
      const rawUrl = match[0];
      if (match.index > cursor) parent.append(document.createTextNode(text.slice(cursor, match.index)));
      if ((rawUrl.startsWith("**") && rawUrl.endsWith("**")) || (rawUrl.startsWith("__") && rawUrl.endsWith("__"))) {
        const strong = document.createElement("strong");
        appendLinkedText(strong, rawUrl.slice(2, -2));
        parent.append(strong);
        cursor = match.index + rawUrl.length;
        continue;
      }
      const url = rawUrl.replace(/[.,;!?)}\]]+$/g, "");
      const trailing = rawUrl.slice(url.length);
      try {
        const parsed = new URL(url);
        if (parsed.protocol === "http:" || parsed.protocol === "https:") {
          const link = document.createElement("a");
          link.href = parsed.href;
          link.target = "_blank";
          link.rel = "noopener noreferrer";
          link.className = "answer-link";
          link.textContent = url;
          parent.append(link, document.createTextNode(trailing));
        } else {
          parent.append(document.createTextNode(rawUrl));
        }
      } catch {
        parent.append(document.createTextNode(rawUrl));
      }
      cursor = match.index + rawUrl.length;
    }
    if (cursor < text.length) parent.append(document.createTextNode(text.slice(cursor)));
  };
  const renderAnswer = (body, text) => {
    let listNode = null;
    let listKind = "";
    let lastOrderedNumber = 0;
    let lastListItem = null;
    String(text).replace(/^\s*Best matching knowledge-base result \(not AI-generated\):\s*/i, '').split(/\r?\n/).forEach((line) => {
      if (!line.trim()) {
        return;
      }
      const sourceTitle = line.match(/^\s*\*\*([^*\n]+)\*\*\s*$/);
      if (sourceTitle) {
        listNode = null;
        listKind = "";
        lastOrderedNumber = 0;
        lastListItem = null;
        const element = document.createElement("h3");
        element.className = "source-title";
        appendLinkedText(element, sourceTitle[1]);
        body.append(element);
        return;
      }
      const heading = line.match(/^#{1,3}\s+(.+)$/) || line.match(/^\s*([A-Z][^.!?]{0,57}:)\s*$/);
      if (heading) {
        listNode = null;
        listKind = "";
        lastOrderedNumber = 0;
        lastListItem = null;
        const element = document.createElement("h3");
        appendLinkedText(element, heading[1]);
        body.append(element);
        return;
      }
      const item = line.match(/^\s*(?:[-*]\s+|(\d+)\.\s+)(.+)$/);
      if (item) {
        const kind = item[1] ? "ol" : "ul";
        if (!listNode || listKind !== kind) {
          listNode = document.createElement(kind);
          listKind = kind;
          body.append(listNode);
        }
        const entry = document.createElement("li");
        if (kind === "ol" && item[1]) {
          const requestedNumber = Number(item[1]);
          const displayNumber = requestedNumber <= lastOrderedNumber ? lastOrderedNumber + 1 : requestedNumber;
          entry.value = displayNumber;
          lastOrderedNumber = displayNumber;
        }
        appendLinkedText(entry, item[2]);
        listNode.append(entry);
        lastListItem = entry;
        return;
      }
      if (lastListItem && /^(Position|Qualifications|Education(?: and qualifications)?|MC Portal projects):/i.test(line.trim())) {
        const detail = document.createElement("p");
        appendLinkedText(detail, line);
        lastListItem.append(detail);
        return;
      }
      listNode = null;
      listKind = "";
      lastListItem = null;
      const paragraph = document.createElement("p");
      appendLinkedText(paragraph, line);
      body.append(paragraph);
    });
  };
  const answerModeLabel = (mode) => ({
    greeting: ["Greeting", "mode-general"],
    "ai-general": ["General AI - not verified CCS information", "mode-general"],
    ai: ["AI answer - based on CCS sources", "mode-grounded"],
    "knowledge-base": ["Knowledge base answer", "mode-grounded"],
    announcement: ["Published announcement", "mode-announcement"],
    unverified: ["No verified CCS match", "mode-unverified"],
    "legacy-sourced": ["References saved", "mode-grounded"],
  })[mode] || null;
  const addMessage = (sender, text, sources = [], mode = "") => {
    welcome?.remove();
    const article = document.createElement("article");
    article.className = `message ${sender}`;
    const meta = document.createElement("div");
    meta.className = "message-meta";
    const badge = document.createElement("span");
    badge.className = "message-badge";
    badge.textContent = sender === "student" ? "Y" : "C";
    const label = document.createElement("span");
    label.textContent = sender === "student" ? "You" : "COMSAI";
    const time = document.createElement("time");
    time.dateTime = new Date().toISOString();
    time.textContent = new Intl.DateTimeFormat(undefined, {
      hour: "numeric",
      minute: "2-digit",
    }).format(new Date());
    meta.append(badge, label);
    const modeInfo = sender === "assistant" ? answerModeLabel(mode) : null;
    if (modeInfo) {
      const modeBadge = document.createElement("span");
      modeBadge.className = `message-mode ${modeInfo[1]}`;
      modeBadge.textContent = modeInfo[0];
      meta.append(modeBadge);
    }
    meta.append(time);
    const body = document.createElement("div");
    body.className = "message-body";
    renderAnswer(body, text);
    article.append(meta, body);
    if (sources.length) {
      const sourceRow = document.createElement("div");
      sourceRow.className = "sources";
      const seenSourceTitles = new Set();
      sources.forEach((source) => {
        const sourceTitle = String(source.title || "").trim();
        const sourceKey = sourceTitle.toLocaleLowerCase();
        if (!sourceTitle || seenSourceTitles.has(sourceKey)) return;
        seenSourceTitles.add(sourceKey);
        const item = source.url
          ? document.createElement("a")
          : document.createElement("span");
        item.className = source.url ? "source-link" : "source-label";
        item.textContent = sourceTitle;
        if (source.url) {
          item.href = source.url;
          item.target = "_blank";
          item.rel = "noopener noreferrer";
        }
        sourceRow.append(item);
      });
      article.append(sourceRow);
    }
    list.append(article);
    scrollToBottom();
  };

  const showError = (text) => {
    const note = document.createElement("div");
    note.className = "error-message";
    note.setAttribute("role", "alert");
    note.textContent = text;
    list.append(note);
    scrollToBottom();
  };

  const showQuotaNotice = (text) => {
    const note = document.createElement("div");
    note.className = "quota-notice";
    note.setAttribute("role", "status");
    note.textContent = text;
    list.append(note);
    scrollToBottom();
  };

  const showMaintenanceNotice = (text) => {
    maintenanceMode = true;
    input.disabled = true;
    sendButton.disabled = true;
    document.querySelector('#new-chat')?.setAttribute('disabled', '');
    document.querySelectorAll('[data-question]').forEach((button) => { button.disabled = true; });
    const note = document.createElement('div');
    note.className = 'maintenance-notice';
    note.setAttribute('role', 'status');
    note.textContent = text;
    list.append(note);
    scrollToBottom();
  };

  const send = async (question, categoryId = null) => {
    const message = question.trim();
    if (!message || busy || message.length > 1000) return;
    busy = true;
    await historyReady;
    input.value = "";
    input.style.height = "auto";
    count.textContent = "0 / 1000";
    addMessage("student", message);
    const typing = document.createElement("div");
    typing.className = "typing";
    typing.textContent = "COMSAI is thinking";
    typing.setAttribute("role", "status");
    list.append(typing);
    sendButton.disabled = true;
    try {
      const response = await fetch("api/chat.php", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-CSRF-Token": csrf },
        body: JSON.stringify({ action: "message", question: message, category_id: categoryId }),
      });
      const payload = await response.json();
      typing.remove();
      if (payload.code === "maintenance") {
        showMaintenanceNotice(payload.error || "COMSAI is under maintenance. Please check back later.");
        return;
      }
      if (payload.code === "daily_student_limit") {
        showQuotaNotice(payload.error || "COMSAI is unavailable for new students today. Please come back tomorrow.");
        return;
      }
      if (!response.ok || !payload.ok)
        throw new Error(
          payload.error ||
            "The assistant could not answer right now. Please try again.",
        );
      if (Array.isArray(payload.messages)) {
        payload.messages.forEach((message) => addMessage("assistant", message.answer, message.sources || [], payload.mode || ""));
      } else {
        addMessage("assistant", payload.answer, payload.sources || [], payload.mode || "");
      }
    } catch (error) {
      typing.remove();
      showError(
        error.message ||
          "Connection problem. Check that Apache and MySQL are running, then try again.",
      );
    } finally {
      busy = false;
      sendButton.disabled = maintenanceMode;
      if (!maintenanceMode) input.focus();
    }
  };

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    send(input.value);
  });
  input.addEventListener("input", () => {
    count.textContent = `${input.value.length} / 1000`;
    input.style.height = "auto";
    input.style.height = `${Math.min(input.scrollHeight, 140)}px`;
  });
  input.addEventListener("keydown", (event) => {
    if (event.key === "Enter" && !event.shiftKey) {
      event.preventDefault();
      form.requestSubmit();
    }
  });
  document
    .querySelectorAll("[data-question]")
    .forEach((button) =>
      button.addEventListener("click", () => send(button.dataset.question, button.dataset.categoryId || null)),
    );
  document.querySelector("#new-chat").addEventListener("click", async () => {
    if (busy) return;
    try {
      const response = await fetch("api/chat.php", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-CSRF-Token": csrf },
        body: JSON.stringify({ action: "clear" }),
      });
      if (!response.ok) {
        const payload = await response.json().catch(() => null);
        if (payload?.code === "maintenance") {
          showMaintenanceNotice(payload.error || "COMSAI is under maintenance. Please check back later.");
          return;
        }
        throw new Error();
      }
      list.replaceChildren();
      const fresh = welcome?.cloneNode(true);
      if (fresh) list.append(fresh);
      location.reload();
    } catch {
      showError("Could not start a new chat. Please refresh the page.");
    }
  });
  historyReady = (async () => {
    const headers = { "Content-Type": "application/json", "X-CSRF-Token": csrf };
    if (new URLSearchParams(location.search).has("new_chat")) {
      const clearResponse = await fetch("api/chat.php", {
        method: "POST",
        headers,
        body: JSON.stringify({ action: "clear" }),
      });
      if (!clearResponse.ok) throw new Error("Could not start a new chat.");
      history.replaceState(null, "", `${location.pathname}${location.hash}`);
    }
    const response = await fetch("api/chat.php", {
      method: "POST",
      headers,
      body: JSON.stringify({ action: "history" }),
    });
    const payload = response.ok ? await response.json() : null;
    if (payload?.ok) payload.messages.forEach((message) => addMessage(
      message.sender === "student" ? "student" : "assistant",
      message.message,
      message.sources || [],
      message.response_mode || "",
    ));
  })().catch(() => {});
})();
