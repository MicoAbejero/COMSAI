(() => {
  const form = document.querySelector("#chat-form");
  const input = document.querySelector("#question");
  const list = document.querySelector("#conversation");
  const welcome = document.querySelector("#welcome");
  const sendButton = document.querySelector("#send-button");
  const count = document.querySelector("#char-count");
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  let busy = false;
  let historyReady = Promise.resolve();

  const scrollToBottom = () => {
    list.scrollTop = list.scrollHeight;
  };
  const addMessage = (sender, text, sources = []) => {
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
    meta.append(badge, label, time);
    const body = document.createElement("div");
    body.className = "message-body";
    body.textContent = text;
    article.append(meta, body);
    if (sources.length) {
      const sourceRow = document.createElement("div");
      sourceRow.className = "sources";
      sources.forEach((source) => {
        const item = source.url
          ? document.createElement("a")
          : document.createElement("span");
        item.className = source.url ? "source-link" : "source-label";
        item.textContent = source.title;
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

  const send = async (question) => {
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
    typing.textContent = "COMSAI is checking verified sources";
    typing.setAttribute("role", "status");
    list.append(typing);
    sendButton.disabled = true;
    try {
      const response = await fetch("api/chat.php", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-CSRF-Token": csrf },
        body: JSON.stringify({ action: "message", question: message }),
      });
      const payload = await response.json();
      typing.remove();
      if (!response.ok || !payload.ok)
        throw new Error(
          payload.error ||
            "The assistant could not answer right now. Please try again.",
        );
      addMessage("assistant", payload.answer, payload.sources || []);
    } catch (error) {
      typing.remove();
      showError(
        error.message ||
          "Connection problem. Check that Apache and MySQL are running, then try again.",
      );
    } finally {
      busy = false;
      sendButton.disabled = false;
      input.focus();
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
      button.addEventListener("click", () => send(button.dataset.question)),
    );
  document.querySelector("#new-chat").addEventListener("click", async () => {
    if (busy) return;
    try {
      const response = await fetch("api/chat.php", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-CSRF-Token": csrf },
        body: JSON.stringify({ action: "clear" }),
      });
      if (!response.ok) throw new Error();
      list.replaceChildren();
      const fresh = welcome?.cloneNode(true);
      if (fresh) list.append(fresh);
      location.reload();
    } catch {
      showError("Could not start a new chat. Please refresh the page.");
    }
  });
  historyReady = fetch("api/chat.php", {
    method: "POST",
    headers: { "Content-Type": "application/json", "X-CSRF-Token": csrf },
    body: JSON.stringify({ action: "history" }),
  })
    .then((response) => response.ok ? response.json() : null)
    .then((payload) => {
      if (payload?.ok) payload.messages.forEach((message) => addMessage(
        message.sender === "student" ? "student" : "assistant",
        message.message,
        message.sources || [],
      ));
    })
    .catch(() => {});
})();
