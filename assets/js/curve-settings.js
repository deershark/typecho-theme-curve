(function (window, document) {
  "use strict";

  var groups = [
    {
      id: "profile",
      icon: "contacts",
      title: "站点基础",
      description: "设置站点身份、简介和基本信息。",
      fields: ["siteAuthorName", "siteAuthorLink", "siteAuthorEmail", "intro", "homeSubtitleMode", "homeSubtitle", "sidebarAuthorDescription", "logoUrl", "recordNumber", "since"]
    },
    {
      id: "appearance",
      icon: "style",
      title: "外观与封面",
      description: "调整主题色、字体、首页 Banner 和文章卡片布局。",
      fields: ["accentColor", "homeTitleFont", "defaultFont", "defaultBanner", "defaultBackground", "defaultBackgroundUrl", "fontSource", "coverLayout", "defaultCovers"]
    },
    {
      id: "navigation",
      icon: "menu",
      title: "导航菜单",
      description: "用列表编辑左上角悬浮菜单，不需要记分隔符格式。",
      fields: ["topLeftMenu"]
    },
    {
      id: "social",
      icon: "people",
      title: "社交链接",
      description: "社交链接会同时用于页脚和侧栏，拖动按钮可以调整显示顺序。",
      fields: ["socialLinks", "sidebarSocialLinks"]
    },
    {
      id: "article",
      icon: "article",
      title: "文章与摘要",
      description: "控制文章列表分页、目录、版权卡片、相关推荐和 FakeGPT 摘要。",
      fields: ["postSize", "fakeGptEnable", "fakeGptClickText", "postLinkTarget", "showToc", "showCopyright", "relatedEnable"]
    },
    {
      id: "interaction",
      icon: "chat",
      title: "评论与互动",
      description: "控制评论区、开往按钮和反馈入口。",
      fields: ["commentEnable", "commentFormPosition", "commentPlaceholder", "commentAuthorShowSensitive", "commentLocationEnable", "reportUrl", "travellingsEnable"]
    },
    {
      id: "footer",
      icon: "date",
      title: "页脚与其他",
      description: "配置页脚头像提示、打赏二维码和倒计时。",
      fields: ["footerAvatarEmoji", "footerAvatarMessage", "rewardWechat", "rewardAlipay", "countdownName", "countdownDate"]
    }
  ];

  var editors = {};

  var socialNameOptions = [
    { value: "", label: "请选择平台" },
    { value: "email", label: "Email" },
    { value: "github", label: "GitHub" },
    { value: "telegram", label: "Telegram" },
    { value: "bilibili", label: "Bilibili" },
    { value: "qq", label: "QQ" },
    { value: "twitter", label: "Twitter / X" },
    { value: "home", label: "Home" }
  ];

  function normalizeSocialName(value) {
    var name = String(value || "").trim();
    var normalized = name.toLowerCase();
    if (normalized.indexOf("github") !== -1) return "github";
    if (normalized.indexOf("email") !== -1 || normalized.indexOf("mail") !== -1 || normalized.indexOf("邮箱") !== -1) return "email";
    if (normalized === "tg" || normalized.indexOf("telegram") !== -1) return "telegram";
    if (normalized.indexOf("bilibili") !== -1) return "bilibili";
    if (normalized === "qq") return "qq";
    if (normalized.indexOf("twitter") !== -1 || normalized === "x" || normalized.indexOf("𝕏") !== -1) return "twitter";
    if (normalized.indexOf("home") !== -1 || normalized.indexOf("主页") !== -1 || normalized.indexOf("首页") !== -1) return "home";
    return name;
  }

  function parseSocialData(value) {
    var seenPlatforms = {};
    return parseArray(value).map(function (item) {
      item = item && typeof item === "object" ? item : {};
      return { name: normalizeSocialName(item.name), url: item.url || "", icon: item.icon || "" };
    }).filter(function (item) {
      if (!item.name || socialNameOptions.every(function (option) { return option.value !== item.name; }) || seenPlatforms[item.name]) return false;
      seenPlatforms[item.name] = true;
      return true;
    });
  }

  function createElement(tag, className, text) {
    var element = document.createElement(tag);
    if (className) element.className = className;
    if (typeof text !== "undefined") element.textContent = text;
    return element;
  }

  function makeFontIcon(icon, className) {
    return createElement("i", (className ? className + " " : "") + "iconfont icon-" + icon);
  }

  function findInput(form, name) {
    return form.querySelector('[name="' + name + '"]');
  }

  function findFieldRow(input, form) {
    var node = input;
    while (node && node !== form) {
      if (node.nodeType === 1 && node.classList && node.classList.contains("typecho-option")) return node;
      node = node.parentNode;
    }
    return input.parentNode;
  }

  function parseArray(value) {
    try {
      var parsed = JSON.parse(value || "[]");
      return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
      return [];
    }
  }

  function encode(value) {
    return JSON.stringify(value);
  }

  function makeInput(value, placeholder, type) {
    var input = document.createElement("input");
    input.type = type || "text";
    input.value = value || "";
    input.placeholder = placeholder || "";
    input.className = "curve-settings-control";
    return input;
  }

  function makeLabel(text) {
    return createElement("label", "curve-settings-control-label", text);
  }

  function makeButton(text, className, title) {
    var button = createElement("button", "curve-settings-button" + (className ? " " + className : ""), text);
    button.type = "button";
    if (title) button.title = title;
    return button;
  }

  function makeControl(text, value, placeholder, type) {
    var wrapper = createElement("label", "curve-settings-control-group");
    wrapper.appendChild(makeLabel(text));
    var input = makeInput(value, placeholder, type);
    wrapper.appendChild(input);
    return { wrapper: wrapper, input: input };
  }

  function makeSelectControl(text, value, options) {
    var wrapper = createElement("label", "curve-settings-control-group");
    wrapper.appendChild(makeLabel(text));
    var select = document.createElement("select");
    select.className = "curve-settings-control";
    options.forEach(function (option) {
      var optionElement = document.createElement("option");
      optionElement.value = option.value;
      optionElement.textContent = option.label;
      select.appendChild(optionElement);
    });
    if (value && !options.some(function (option) { return option.value === value; })) {
      var legacyOption = document.createElement("option");
      legacyOption.value = value;
      legacyOption.textContent = "现有值：" + value;
      select.appendChild(legacyOption);
    }
    select.value = value || "";
    wrapper.appendChild(select);
    return { wrapper: wrapper, input: select };
  }

  function makeRow(actions) {
    var row = createElement("div", "curve-settings-editor-row");
    var fields = createElement("div", "curve-settings-editor-fields");
    var buttons = createElement("div", "curve-settings-editor-actions");
    row.appendChild(fields);
    row.appendChild(buttons);

    var up = makeButton("↑", "curve-settings-icon-button", "上移");
    var down = makeButton("↓", "curve-settings-icon-button", "下移");
    var remove = makeButton("删除", "curve-settings-delete-button", "删除这一项");
    buttons.appendChild(up);
    buttons.appendChild(down);
    buttons.appendChild(remove);
    actions.up = up;
    actions.down = down;
    actions.remove = remove;
    return { row: row, fields: fields };
  }

  function addEditorFrame(input, label, description, form) {
    var row = findFieldRow(input, form);
    if (!row) return null;
    row.classList.add("curve-settings-json-field");
    input.style.display = "none";

    var editor = createElement("div", "curve-settings-editor");
    var heading = createElement("div", "curve-settings-editor-heading");
    heading.appendChild(createElement("strong", "curve-settings-editor-title", label));
    heading.appendChild(createElement("span", "curve-settings-editor-description", description));
    editor.appendChild(heading);
    var list = createElement("div", "curve-settings-editor-list");
    editor.appendChild(list);
    var add = makeButton("＋ 添加一项", "curve-settings-add-button");
    editor.appendChild(add);

    var target = input.parentNode || row;
    target.appendChild(editor);
    return { editor: editor, list: list, add: add, input: input };
  }

  function renderSocialEditor(form) {
    var input = findInput(form, "socialLinks");
    var frame = addEditorFrame(input, "链接列表", "每个平台只能配置一次，图标由平台枚举自动决定。", form);
    if (!frame) return;

    var data = parseSocialData(input.value);

    function hasAvailablePlatform() {
      return socialNameOptions.some(function (option) {
        return option.value && !data.some(function (item) { return item.name === option.value; });
      });
    }

    function updateAddButton() {
      frame.add.disabled = !hasAvailablePlatform();
      frame.add.title = frame.add.disabled ? "所有平台都已配置" : "添加一个社交平台";
    }

    function socialIconName(name) {
      return name || "link";
    }

    function renderSocialIcon(preview, name) {
      preview.innerHTML = "";
      if (name === "twitter") {
        preview.appendChild(createElement("span", "curve-settings-social-mark", "𝕏"));
      } else {
        preview.appendChild(makeFontIcon(socialIconName(name), "curve-settings-social-icon"));
      }
      preview.appendChild(createElement("span", "curve-settings-social-icon-name", name || "未选择平台"));
    }

    function sync() {
      input.value = encode(data.map(function (item) {
        return { name: item.name, url: String(item.url || "").trim() };
      }));
      input.dispatchEvent(new Event("input", { bubbles: true }));
      if (editors.sidebar) editors.sidebar.render();
      updateAddButton();
    }

    function render() {
      frame.list.innerHTML = "";
      data.forEach(function (item, index) {
        var actions = {};
        var parts = makeRow(actions);
        var name = makeSelectControl("平台", item.name, socialNameOptions);
        var url = makeControl("链接", item.url, "https://example.com", "url");
        var icon = createElement("div", "curve-settings-social-icon-control");
        icon.appendChild(makeLabel("图标"));
        var iconPreview = createElement("div", "curve-settings-social-icon-preview");
        renderSocialIcon(iconPreview, item.name);
        icon.appendChild(iconPreview);
        parts.fields.appendChild(name.wrapper);
        parts.fields.appendChild(url.wrapper);
        parts.fields.appendChild(icon);
        name.input.addEventListener("change", function () {
          var nextName = normalizeSocialName(name.input.value);
          var duplicate = nextName && data.some(function (candidate, candidateIndex) {
            return candidateIndex !== index && candidate.name === nextName;
          });
          if (duplicate) {
            window.alert("同一个社交平台只能配置一次。");
            name.input.value = item.name;
            return;
          }
          item.name = nextName;
          renderSocialIcon(iconPreview, item.name);
          sync();
        });
        url.input.addEventListener("input", function () {
          item.url = url.input.value.trim();
          sync();
        });
        actions.up.addEventListener("click", function () {
          if (index > 0) {
            var previous = data[index - 1];
            data[index - 1] = data[index];
            data[index] = previous;
            render();
            sync();
          }
        });
        actions.down.addEventListener("click", function () {
          if (index < data.length - 1) {
            var next = data[index + 1];
            data[index + 1] = data[index];
            data[index] = next;
            render();
            sync();
          }
        });
        actions.remove.addEventListener("click", function () {
          data.splice(index, 1);
          render();
          sync();
        });
        frame.list.appendChild(parts.row);
      });
      if (!data.length) frame.list.appendChild(createElement("p", "curve-settings-empty", "还没有社交链接，点击下方按钮添加。"));
      updateAddButton();
    }

    frame.add.addEventListener("click", function () {
      if (!hasAvailablePlatform()) return;
      data.push({ name: "", url: "", icon: "" });
      render();
      sync();
    });
    editors.social = {
      data: data,
      render: render,
      sync: sync,
      reload: function () {
        var fresh = parseSocialData(input.value);
        data.splice.apply(data, [0, data.length].concat(fresh));
        render();
      }
    };
    render();
  }

  function renderMenuEditor(form) {
    var input = findInput(form, "topLeftMenu");
    var frame = addEditorFrame(input, "菜单分类", "先添加分类，再在分类下添加菜单 item；图标可以填写 iconfont 名称或图片地址。", form);
    if (!frame) return;
    frame.add.textContent = "＋ 添加分类";

    var grouped = {};
    var data = [];
    function getCategory(groupName) {
      groupName = String(groupName || "其他").trim() || "其他";
      if (!grouped[groupName]) {
        grouped[groupName] = { name: groupName, items: [] };
        data.push(grouped[groupName]);
      }
      return grouped[groupName];
    }

    function loadData(value) {
      grouped = {};
      data.splice(0, data.length);
      parseArray(value).forEach(function (item) {
        item = item && typeof item === "object" ? item : {};
        if (Array.isArray(item.items)) {
          var nestedCategory = getCategory(item.name || item.group);
          item.items.forEach(function (nestedItem) {
            nestedItem = nestedItem && typeof nestedItem === "object" ? nestedItem : {};
            nestedCategory.items.push({
              name: nestedItem.name || "",
              url: nestedItem.url || "",
              icon: nestedItem.icon || ""
            });
          });
          return;
        }
        var category = getCategory(item.group);
        category.items.push({
          name: item.name || "",
          url: item.url || "",
          icon: item.icon || ""
        });
      });
    }

    loadData(input.value);

    function sync() {
      var categories = data.map(function (category) {
        return {
          name: String(category.name || "").trim(),
          items: category.items.map(function (item) {
            return {
              name: String(item.name || "").trim(),
              url: String(item.url || "").trim(),
              icon: String(item.icon || "").trim()
            };
          })
        };
      });
      input.value = encode(categories);
      input.dispatchEvent(new Event("input", { bubbles: true }));
    }

    function render() {
      frame.list.innerHTML = "";
      data.forEach(function (category, categoryIndex) {
        var categoryElement = createElement("section", "curve-settings-menu-category");
        var categoryHeader = createElement("div", "curve-settings-menu-category-header");
        var categoryControl = makeControl("分类", category.name, "例如：文库");
        categoryControl.wrapper.classList.add("curve-settings-menu-category-name");
        categoryHeader.appendChild(categoryControl.wrapper);

        var categoryActions = createElement("div", "curve-settings-menu-category-actions");
        var categoryUp = makeButton("↑", "curve-settings-icon-button", "上移分类");
        var categoryDown = makeButton("↓", "curve-settings-icon-button", "下移分类");
        var categoryRemove = makeButton("删除分类", "curve-settings-delete-button", "删除整个分类");
        categoryActions.appendChild(categoryUp);
        categoryActions.appendChild(categoryDown);
        categoryActions.appendChild(categoryRemove);
        categoryHeader.appendChild(categoryActions);
        categoryElement.appendChild(categoryHeader);

        var itemList = createElement("div", "curve-settings-menu-item-list");
        category.items.forEach(function (item, itemIndex) {
          var actions = {};
          var parts = makeRow(actions);
          var name = makeControl("名称", item.name, "例如：文章归档");
          var url = makeControl("链接", item.url, "https://example.com", "url");
          var icon = makeControl("图标", item.icon, "article 或图片地址");
          parts.fields.appendChild(name.wrapper);
          parts.fields.appendChild(url.wrapper);
          parts.fields.appendChild(icon.wrapper);
          [name.input, url.input, icon.input].forEach(function (control) {
            control.addEventListener("input", function () {
              item.name = name.input.value.trim();
              item.url = url.input.value.trim();
              item.icon = icon.input.value.trim();
              sync();
            });
          });
          actions.up.addEventListener("click", function () {
          if (itemIndex > 0) {
            var previous = category.items[itemIndex - 1];
            category.items[itemIndex - 1] = category.items[itemIndex];
            category.items[itemIndex] = previous;
            render();
            sync();
          }
          });
          actions.down.addEventListener("click", function () {
          if (itemIndex < category.items.length - 1) {
            var next = category.items[itemIndex + 1];
            category.items[itemIndex + 1] = category.items[itemIndex];
            category.items[itemIndex] = next;
            render();
            sync();
          }
          });
          actions.remove.addEventListener("click", function () {
          category.items.splice(itemIndex, 1);
          render();
          sync();
          });
          itemList.appendChild(parts.row);
        });

        if (!category.items.length) {
          itemList.appendChild(createElement("p", "curve-settings-empty", "这个分类还没有 item。"));
        }
        categoryElement.appendChild(itemList);

        var addItem = makeButton("＋ 添加 item", "curve-settings-add-item-button");
        addItem.addEventListener("click", function () {
          category.items.push({ name: "", url: "", icon: "" });
          render();
          sync();
        });
        categoryElement.appendChild(addItem);

        categoryControl.input.addEventListener("input", function () {
          category.name = categoryControl.input.value.trim();
          sync();
        });
        categoryUp.addEventListener("click", function () {
          if (categoryIndex > 0) {
            var previous = data[categoryIndex - 1];
            data[categoryIndex - 1] = data[categoryIndex];
            data[categoryIndex] = previous;
            render();
            sync();
          }
        });
        categoryDown.addEventListener("click", function () {
          if (categoryIndex < data.length - 1) {
            var next = data[categoryIndex + 1];
            data[categoryIndex + 1] = data[categoryIndex];
            data[categoryIndex] = next;
            render();
            sync();
          }
        });
        categoryRemove.addEventListener("click", function () {
          data.splice(categoryIndex, 1);
          render();
          sync();
        });
        frame.list.appendChild(categoryElement);
      });
      if (!data.length) frame.list.appendChild(createElement("p", "curve-settings-empty", "还没有菜单分类，点击下方按钮添加。"));
    }

    frame.add.addEventListener("click", function () {
      data.push({ name: "新分类", items: [] });
      render();
      sync();
    });
    editors.menu = {
      data: data,
      render: render,
      sync: sync,
      reload: function () {
        loadData(input.value);
        render();
      }
    };
    render();
  }

  function renderCoverEditor(form) {
    var input = findInput(form, "defaultCovers");
    var frame = addEditorFrame(input, "封面列表", "支持外部图片地址；文章没有单独封面时会从列表中随机选择。", form);
    if (!frame) return;
    var data = parseArray(input.value).map(function (item) { return String(item || ""); });

    function sync() {
      input.value = encode(data);
      input.dispatchEvent(new Event("input", { bubbles: true }));
    }

    function render() {
      frame.list.innerHTML = "";
      data.forEach(function (url, index) {
        var actions = {};
        var parts = makeRow(actions);
        var control = makeControl("图片地址", url, "https://example.com/cover.jpg", "url");
        var preview = createElement("div", "curve-settings-cover-preview");
        if (url) {
          var image = document.createElement("img");
          image.src = url;
          image.alt = "封面预览";
          image.addEventListener("error", function () { preview.classList.add("is-error"); });
          preview.appendChild(image);
        } else {
          preview.textContent = "暂无预览";
        }
        parts.fields.appendChild(control.wrapper);
        parts.fields.appendChild(preview);
        control.input.addEventListener("input", function () {
          data[index] = control.input.value.trim();
          render();
          sync();
        });
        actions.up.addEventListener("click", function () {
          if (index > 0) {
            var previous = data[index - 1];
            data[index - 1] = data[index];
            data[index] = previous;
            render();
            sync();
          }
        });
        actions.down.addEventListener("click", function () {
          if (index < data.length - 1) {
            var next = data[index + 1];
            data[index + 1] = data[index];
            data[index] = next;
            render();
            sync();
          }
        });
        actions.remove.addEventListener("click", function () {
          data.splice(index, 1);
          render();
          sync();
        });
        frame.list.appendChild(parts.row);
      });
      if (!data.length) frame.list.appendChild(createElement("p", "curve-settings-empty", "还没有默认封面，点击下方按钮添加。"));
    }

    frame.add.addEventListener("click", function () {
      data.push("");
      render();
      sync();
    });
    editors.covers = {
      data: data,
      render: render,
      sync: sync,
      reload: function () {
        var fresh = parseArray(input.value).map(function (item) { return String(item || ""); });
        data.splice.apply(data, [0, data.length].concat(fresh));
        render();
      }
    };
    render();
  }

  function renderSidebarEditor(form) {
    var input = findInput(form, "sidebarSocialLinks");
    var frame = addEditorFrame(input, "侧栏显示选择", "从上面的社交链接中选择最多两个；不选择时默认使用前两个。", form);
    if (!frame) return;
    frame.add.style.display = "none";
    var list = frame.list;

    function getNames() {
      var names = [];
      var data = editors.social ? editors.social.data : parseArray(findInput(form, "socialLinks").value);
      data.forEach(function (item) {
        var name = item && typeof item === "object" ? String(item.name || "").trim() : "";
        if (name && names.indexOf(name) === -1) names.push(name);
      });
      return names;
    }

    function render() {
      var selected = parseArray(input.value).map(normalizeSocialName);
      var names = getNames();
      selected = selected.filter(function (name) { return names.indexOf(name) !== -1; });
      input.value = encode(selected);
      list.innerHTML = "";
      if (!names.length) {
        list.appendChild(createElement("p", "curve-settings-empty", "请先在上方添加社交链接。"));
        input.value = "[]";
        return;
      }
      names.forEach(function (name) {
        var label = createElement("label", "curve-settings-check-item");
        var checkbox = document.createElement("input");
        checkbox.type = "checkbox";
        checkbox.value = name;
        checkbox.checked = selected.indexOf(name) !== -1;
        if (checkbox.checked) label.classList.add("is-checked");
        label.appendChild(checkbox);
        label.appendChild(createElement("span", "curve-settings-check-name", name));
        checkbox.addEventListener("change", function () {
          var checked = list.querySelectorAll("input[type=checkbox]:checked");
          if (checked.length > 2) {
            checkbox.checked = false;
            window.alert("侧栏最多选择两个社交链接。");
            return;
          }
          var values = [];
          Array.prototype.forEach.call(checked, function (item) { values.push(item.value); });
          label.classList.toggle("is-checked", checkbox.checked);
          input.value = encode(values);
          input.dispatchEvent(new Event("input", { bubbles: true }));
        });
        list.appendChild(label);
      });
    }

    editors.sidebar = { render: render, reload: render };
    render();
  }

  function enhanceScalarFields(form) {
    var colorInput = findInput(form, "accentColor");
    if (colorInput) {
      var colorPicker = document.createElement("input");
      colorPicker.type = "color";
      colorPicker.className = "curve-settings-color-picker";
      colorPicker.value = /^#[0-9a-f]{6}$/i.test(colorInput.value) ? colorInput.value : "#425aef";
      colorInput.classList.add("curve-settings-color-text");
      colorInput.setAttribute("inputmode", "text");
      colorInput.setAttribute("autocomplete", "off");

      var colorControl = createElement("span", "curve-settings-color-control");
      var colorParent = colorInput.parentNode;
      colorParent.insertBefore(colorControl, colorInput);
      colorControl.appendChild(colorInput);
      colorControl.appendChild(colorPicker);

      var colorReset = makeButton("恢复默认", "curve-settings-color-reset", "清除自定义强调色");
      colorControl.appendChild(colorReset);

      function normalizeColor(value) {
        value = String(value || "").trim();
        if (/^#[0-9a-f]{3}$/i.test(value)) {
          return "#" + value[1] + value[1] + value[2] + value[2] + value[3] + value[3];
        }
        return value;
      }

      function updateColorState() {
        var value = normalizeColor(colorInput.value);
        var valid = value === "" || /^#[0-9a-f]{6}$/i.test(value);
        colorInput.classList.toggle("is-invalid", !valid);
        colorReset.disabled = value === "";
        if (/^#[0-9a-f]{6}$/i.test(value)) colorPicker.value = value;
      }

      colorPicker.addEventListener("input", function () {
        colorInput.value = colorPicker.value;
        colorInput.dispatchEvent(new Event("input", { bubbles: true }));
        updateColorState();
      });
      colorInput.addEventListener("input", function () {
        updateColorState();
      });
      colorReset.addEventListener("click", function () {
        colorInput.value = "";
        colorInput.dispatchEvent(new Event("input", { bubbles: true }));
        updateColorState();
        colorInput.focus();
      });
      updateColorState();
    }

    ["since", "countdownDate"].forEach(function (name) {
      var input = findInput(form, name);
      if (!input) return;
      input.type = "date";
      input.classList.add("curve-settings-date-input");
      input.setAttribute("autocomplete", "off");

      var dateControl = createElement("span", "curve-settings-date-control");
      var dateParent = input.parentNode;
      dateParent.insertBefore(dateControl, input);
      var dateInputWrap = createElement("span", "curve-settings-date-input-wrap");
      dateControl.appendChild(dateInputWrap);
      dateInputWrap.appendChild(input);

      var datePicker = makeButton("", "curve-settings-date-picker", "选择日期");
      datePicker.appendChild(makeFontIcon("date", "curve-settings-date-picker-icon"));
      datePicker.addEventListener("click", function () {
        input.focus();
        if (typeof input.showPicker === "function") {
          try {
            input.showPicker();
            return;
          } catch (error) {
            // Fall through to the native click fallback.
          }
        }
        input.click();
      });
      dateInputWrap.appendChild(datePicker);

      var dateClear = makeButton("清空", "curve-settings-date-clear", "清空日期");
      dateControl.appendChild(dateClear);
      function updateDateState() {
        dateClear.disabled = input.value === "";
      }
      input.addEventListener("input", updateDateState);
      dateClear.addEventListener("click", function () {
        input.value = "";
        input.dispatchEvent(new Event("input", { bubbles: true }));
        updateDateState();
        input.focus();
      });
      updateDateState();
    });

    var postSize = findInput(form, "postSize");
    if (postSize) {
      postSize.type = "number";
      postSize.min = "1";
      postSize.max = "100";
    }

  }

  function bindDefaultBackgroundVisibility(form) {
    var defaultBackground = findInput(form, "defaultBackground");
    var defaultBackgroundUrl = findInput(form, "defaultBackgroundUrl");
    if (!defaultBackground || !defaultBackgroundUrl) return;
    defaultBackgroundUrl.type = "url";
    defaultBackgroundUrl.setAttribute("autocomplete", "off");
    var row = findFieldRow(defaultBackgroundUrl, form);
    if (!row) return;

    function sync() {
      var visible = defaultBackground.value === "image";
      row.hidden = !visible;
      row.style.display = visible ? "" : "none";
    }
    defaultBackground.addEventListener("change", sync);
    defaultBackground.addEventListener("input", sync);
    sync();
  }

  function bindHomeSubtitleVisibility(form) {
    var homeSubtitleMode = findInput(form, "homeSubtitleMode");
    var homeSubtitle = findInput(form, "homeSubtitle");
    if (!homeSubtitleMode || !homeSubtitle) return;
    var row = findFieldRow(homeSubtitle, form);
    if (!row) return;

    function sync() {
      var visible = homeSubtitleMode.value === "custom";
      row.hidden = !visible;
      row.style.display = visible ? "" : "none";
    }
    homeSubtitleMode.addEventListener("change", sync);
    homeSubtitleMode.addEventListener("input", sync);
    sync();
  }

  function getConfigFieldNames() {
    var names = [];
    groups.forEach(function (group) {
      group.fields.forEach(function (name) {
        if (names.indexOf(name) === -1) names.push(name);
      });
    });
    return names;
  }

  function isJsonConfigField(name) {
    return ["defaultCovers", "topLeftMenu", "socialLinks", "sidebarSocialLinks"].indexOf(name) !== -1;
  }

  function readConfig(form) {
    var options = {};
    getConfigFieldNames().forEach(function (name) {
      var fields = form.querySelectorAll('[name="' + name + '"]');
      if (!fields.length) return;
      var first = fields[0];
      if ((first.type === "radio" || first.type === "checkbox")) {
        var checked = form.querySelector('[name="' + name + '"]:checked');
        if (checked) options[name] = checked.value;
        return;
      }
      var value = first.value;
      if (isJsonConfigField(name)) {
        try {
          value = JSON.parse(value || "[]");
        } catch (error) {
          value = value;
        }
      }
      options[name] = value;
    });
    return {
      format: "curve-theme-config",
      version: 1,
      exportedAt: new Date().toISOString(),
      options: options
    };
  }

  function writeConfig(form, payload) {
    var source = payload && payload.options && typeof payload.options === "object" ? payload.options : payload;
    if (!source || typeof source !== "object" || Array.isArray(source)) throw new Error("配置文件格式不正确");

    getConfigFieldNames().forEach(function (name) {
      if (!Object.prototype.hasOwnProperty.call(source, name)) return;
      var fields = form.querySelectorAll('[name="' + name + '"]');
      if (!fields.length) return;
      var first = fields[0];
      var value = source[name];
      if (first.type === "radio" || first.type === "checkbox") {
        Array.prototype.forEach.call(fields, function (field) {
          field.checked = String(field.value) === String(value);
          field.dispatchEvent(new Event("change", { bubbles: true }));
        });
        return;
      }
      if (isJsonConfigField(name) && typeof value !== "string") {
        value = JSON.stringify(value);
      }
      first.value = value === null || typeof value === "undefined" ? "" : String(value);
      first.dispatchEvent(new Event("input", { bubbles: true }));
      first.dispatchEvent(new Event("change", { bubbles: true }));
    });

    Object.keys(editors).forEach(function (key) {
      if (editors[key].reload) editors[key].reload();
    });
  }

  function createConfigTransfer(form) {
    var panel = createElement("div", "curve-settings-transfer-panel");
    var notice = createElement("div", "curve-settings-save-notice");
    var noticeIcon = makeFontIcon("correct", "curve-settings-save-notice-icon");
    var noticeText = createElement("span", "curve-settings-save-notice-text", "当前配置已保存");
    notice.appendChild(noticeIcon);
    notice.appendChild(noticeText);

    var transfer = createElement("div", "curve-settings-transfer");
    transfer.appendChild(createElement("span", "curve-settings-transfer-label", "配置文件"));
    var exportButton = makeButton("", "curve-settings-transfer-button curve-settings-export-button");
    var importButton = makeButton("", "curve-settings-transfer-button curve-settings-import-button");
    exportButton.appendChild(makeFontIcon("download", "curve-settings-transfer-icon"));
    exportButton.appendChild(createElement("span", "curve-settings-transfer-button-text", "导出配置"));
    importButton.appendChild(makeFontIcon("arrow-up", "curve-settings-transfer-icon"));
    importButton.appendChild(createElement("span", "curve-settings-transfer-button-text", "导入配置"));
    var status = createElement("span", "curve-settings-transfer-status");
    var fileInput = document.createElement("input");
    fileInput.type = "file";
    fileInput.accept = "application/json,.json";
    fileInput.hidden = true;
    var savedPayload = null;
    var isDirty = false;

    function setDirtyState(value) {
      isDirty = value;
      notice.classList.toggle("is-dirty", value);
      noticeIcon.classList.toggle("icon-correct", !value);
      noticeIcon.classList.toggle("icon-time", value);
      noticeText.textContent = value ? "当前有未保存的配置修改，请保存设置" : "当前配置已保存";
    }

    function updateDirtyState() {
      if (!savedPayload) return;
      setDirtyState(JSON.stringify(readConfig(form).options) !== JSON.stringify(savedPayload.options));
    }

    function initialize() {
      savedPayload = readConfig(form);
      setDirtyState(false);
      form.addEventListener("input", updateDirtyState);
      form.addEventListener("change", updateDirtyState);
    }

    exportButton.addEventListener("click", function () {
      if (isDirty && !window.confirm("当前配置有未保存的修改。导出的文件将使用上一次已保存的配置，当前修改不会包含在导出文件中。是否继续导出？")) return;
      var payload = savedPayload || readConfig(form);
      var blob = new Blob([JSON.stringify(payload, null, 2)], { type: "application/json;charset=utf-8" });
      var link = document.createElement("a");
      link.href = URL.createObjectURL(blob);
      var now = new Date();
      function pad(value) { return String(value).padStart(2, "0"); }
      var timestamp = now.getFullYear() + "-" + pad(now.getMonth() + 1) + "-" + pad(now.getDate()) + "-" + pad(now.getHours()) + "-" + pad(now.getMinutes()) + "-" + pad(now.getSeconds());
      link.download = "curve-theme-config-" + timestamp + ".json";
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.setTimeout(function () { URL.revokeObjectURL(link.href); }, 0);
      status.textContent = isDirty ? "已导出上次保存的配置" : "配置已导出";
    });

    importButton.addEventListener("click", function () {
      fileInput.click();
    });
    fileInput.addEventListener("change", function () {
      var file = fileInput.files && fileInput.files[0];
      if (!file) return;
      var reader = new FileReader();
      reader.onload = function () {
        try {
          writeConfig(form, JSON.parse(reader.result));
          updateDirtyState();
          status.textContent = "配置已导入，请保存设置";
        } catch (error) {
          status.textContent = error.message || "配置导入失败";
        }
        fileInput.value = "";
      };
      reader.onerror = function () {
        status.textContent = "配置文件读取失败";
        fileInput.value = "";
      };
      reader.readAsText(file);
    });

    transfer.appendChild(importButton);
    transfer.appendChild(exportButton);
    transfer.appendChild(status);
    transfer.appendChild(fileInput);
    panel.appendChild(notice);
    panel.appendChild(transfer);
    return { element: panel, initialize: initialize };
  }

  function bindSectionNavigation(navList, sections) {
    var buttons = navList.querySelectorAll("[data-curve-settings-target]");
    var activeId = "";
    var scrollOffset = 48;

    function getScrollOffset() {
      var nav = navList.parentNode;
      if (window.innerWidth <= 700 && nav) {
        var navTop = parseFloat(window.getComputedStyle(nav).top) || 0;
        return navTop + nav.offsetHeight + 12;
      }
      return scrollOffset;
    }

    function setActive(id) {
      if (!id || activeId === id) return;
      activeId = id;
      Array.prototype.forEach.call(buttons, function (button) {
        var selected = button.getAttribute("data-curve-settings-target") === id;
        button.classList.toggle("is-active", selected);
        button.setAttribute("aria-current", selected ? "true" : "false");
      });
    }

    function scrollToSection(section) {
      if (!section) return;
      var top = section.getBoundingClientRect().top + window.pageYOffset - getScrollOffset();
      window.scrollTo({ top: Math.max(0, top), behavior: "smooth" });
      setActive(section.id.replace("curve-settings-section-", ""));
    }

    Array.prototype.forEach.call(buttons, function (button) {
      button.addEventListener("click", function () {
        var targetId = button.getAttribute("data-curve-settings-target");
        scrollToSection(document.getElementById("curve-settings-section-" + targetId));
      });
    });

    if ("IntersectionObserver" in window) {
      var observer = new IntersectionObserver(function (entries) {
        var visible = entries.filter(function (entry) { return entry.isIntersecting; });
        if (!visible.length) return;
        visible.sort(function (a, b) { return a.boundingClientRect.top - b.boundingClientRect.top; });
        setActive(visible[0].target.id.replace("curve-settings-section-", ""));
      }, { root: null, rootMargin: "-12% 0px -70% 0px", threshold: 0 });
      sections.forEach(function (section) { observer.observe(section); });
    } else {
      var ticking = false;
      var updateActive = function () {
        var current = sections[0];
        sections.forEach(function (section) {
          if (section.getBoundingClientRect().top <= scrollOffset + 80) current = section;
        });
        if (current) setActive(current.id.replace("curve-settings-section-", ""));
        ticking = false;
      };
      window.addEventListener("scroll", function () {
        if (!ticking) {
          window.requestAnimationFrame(updateActive);
          ticking = true;
        }
      }, { passive: true });
      updateActive();
    }

    setActive(sections.length ? sections[0].id.replace("curve-settings-section-", "") : "");
  }

  function bindBottomDockState(dock) {
    var ticking = false;

    function updateState() {
      var rect = dock.getBoundingClientRect();
      var atNaturalEnd = rect.bottom < window.innerHeight - 2;
      dock.classList.toggle("is-at-page-end", atNaturalEnd);
      ticking = false;
    }

    function scheduleUpdate() {
      if (ticking) return;
      ticking = true;
      window.requestAnimationFrame(updateState);
    }

    window.addEventListener("scroll", scheduleUpdate, { passive: true });
    window.addEventListener("resize", scheduleUpdate);
    updateState();
  }

  function buildLayout(form) {
    var firstInput = findInput(form, "siteAuthorName");
    var firstRow = findFieldRow(firstInput, form);
    if (!firstRow) return;

    var app = createElement("div", "curve-settings-app");
    var configTransfer = createConfigTransfer(form);
    form.insertBefore(app, firstRow);

    var layout = createElement("div", "curve-settings-layout");
    var nav = createElement("aside", "curve-settings-nav");
    nav.setAttribute("aria-label", "主题设置分类");
    nav.appendChild(createElement("div", "curve-settings-nav-title", "配置分类"));
    nav.appendChild(createElement("p", "curve-settings-nav-description", "选择分类快速定位设置。"));
    var navList = createElement("nav", "curve-settings-nav-list");
    var content = createElement("div", "curve-settings-content");
    var sections = [];
    nav.appendChild(navList);
    layout.appendChild(nav);
    layout.appendChild(content);
    app.appendChild(layout);

    groups.forEach(function (group) {
      var section = createElement("section", "curve-settings-section curve-settings-section-" + group.id);
      section.id = "curve-settings-section-" + group.id;
      section.setAttribute("data-curve-settings-section", group.id);
      var header = createElement("div", "curve-settings-section-header");
      header.appendChild(makeFontIcon(group.icon, "curve-settings-section-icon"));
      var heading = createElement("div", "curve-settings-section-heading");
      heading.appendChild(createElement("h2", "curve-settings-section-title", group.title));
      heading.appendChild(createElement("p", "curve-settings-section-description", group.description));
      header.appendChild(heading);
      section.appendChild(header);
      var body = createElement("div", "curve-settings-section-body");
      group.fields.forEach(function (name) {
        var input = findInput(form, name);
        if (!input) return;
        var row = findFieldRow(input, form);
        if (!row) return;
        row.classList.add("curve-settings-native-field");
        body.appendChild(row);
      });
      section.appendChild(body);
      content.appendChild(section);
      sections.push(section);

      var navButton = createElement("button", "curve-settings-nav-button");
      navButton.type = "button";
      navButton.setAttribute("data-curve-settings-target", group.id);
      navButton.setAttribute("aria-current", "false");
      navButton.appendChild(makeFontIcon(group.icon, "curve-settings-nav-icon"));
      navButton.appendChild(createElement("span", "curve-settings-nav-text", group.title));
      navList.appendChild(navButton);
    });

    bindSectionNavigation(navList, sections);
    var bottomDock = createElement("div", "curve-settings-bottom-dock");
    var bottomBar = createElement("div", "curve-settings-bottom-bar");
    bottomBar.appendChild(configTransfer.element);
    var submit = form.querySelector('button[type="submit"], input[type="submit"]');
    if (submit) {
      var saveActions = createElement("div", "curve-settings-bottom-actions");
      var saveButton = makeButton("", "curve-settings-save-button");
      saveButton.appendChild(makeFontIcon("correct", "curve-settings-save-icon"));
      saveButton.appendChild(createElement("span", "curve-settings-save-text", submit.textContent || submit.value || "保存设置"));
      saveButton.addEventListener("click", function () {
        submit.click();
      });
      submit.style.display = "none";
      saveActions.appendChild(saveButton);
      bottomBar.appendChild(saveActions);
    }
    bottomDock.appendChild(bottomBar);
    content.appendChild(bottomDock);
    bindBottomDockState(bottomDock);
    return configTransfer;
  }

  function init() {
    var form = document.querySelector(".typecho-page-main form") || document.querySelector("form");
    if (!form || !findInput(form, "siteAuthorName") || form.dataset.curveSettingsReady === "1") return;
    form.dataset.curveSettingsReady = "1";

    var configTransfer = buildLayout(form);
    bindDefaultBackgroundVisibility(form);
    bindHomeSubtitleVisibility(form);
    renderCoverEditor(form);
    renderMenuEditor(form);
    renderSocialEditor(form);
    renderSidebarEditor(form);
    enhanceScalarFields(form);
    form.addEventListener("submit", function () {
      Object.keys(editors).forEach(function (key) {
        if (editors[key].sync) editors[key].sync();
      });
    });
    if (configTransfer && configTransfer.initialize) configTransfer.initialize();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})(window, document);
