/**
 * Atelier Visual Builder — Full-Screen Visual Studio & Drag-and-Drop Engine
 */
(function ($) {
	'use strict';

	if (typeof window.AVB_STUDIO === 'undefined') {
		return;
	}

	var CFG = window.AVB_STUDIO;

	/* ==========================================================================
	   1. Studio Hub Dashboard Handlers (when on Hub screen)
	   ========================================================================== */
	function initHubDashboard() {
		var createBtn = document.getElementById('avb-create-page-btn');
		if (createBtn) {
			createBtn.addEventListener('click', function () {
				var titleInput = document.getElementById('avb-new-page-title');
				var tplSelect = document.getElementById('avb-new-page-template');
				createNewBuilderPage(
					titleInput ? titleInput.value.trim() : '',
					tplSelect ? tplSelect.value : '',
					createBtn
				);
			});
		}

		document.querySelectorAll('.avb-hub-use-tpl-btn').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var tplId = btn.getAttribute('data-template');
				var title = btn.getAttribute('data-title') || 'Atelier Page';
				createNewBuilderPage(title, tplId, btn);
			});
		});
	}

	function createNewBuilderPage(title, templateId, btnEl) {
		var origText = btnEl ? btnEl.textContent : '';
		if (btnEl) {
			btnEl.disabled = true;
			btnEl.textContent = 'Creating Page...';
		}

		$.post(CFG.ajaxUrl, {
			action: 'avb_create_page',
			nonce: CFG.nonce,
			title: title || 'Atelier Custom Page',
			template_id: templateId || '',
		}).done(function (res) {
			if (res && res.success && res.data && res.data.editorUrl) {
				window.location.href = res.data.editorUrl;
			} else {
				alert((res && res.data && res.data.message) || 'Could not create page.');
				if (btnEl) {
					btnEl.disabled = false;
					btnEl.textContent = origText;
				}
			}
		});
	}

	/* ==========================================================================
	   2. Full-Screen Visual Builder Engine
	   ========================================================================== */
	var Studio = {
		elements: Array.isArray(CFG.elements) ? JSON.parse(JSON.stringify(CFG.elements)) : [],
		pageSettings: CFG.pageSettings || { layout: 'full-width', bg_color: '#fbf9f6' },
		selectedId: null,
		inspectorMode: 'element', // 'element' | 'page'
		activeTab: 'content', // 'content' | 'style' | 'advanced'
		htmlCache: {},
		history: [],
		historyIndex: -1,
		renderTimer: null,
		draggedWidgetType: null,
		draggedElementId: null,

		init: function () {
			if (!document.getElementById('avb-studio-app')) {
				initHubDashboard();
				return;
			}

			this.pushHistory();
			this.renderWidgetCatalog('');
			this.renderTemplateModal();
			this.bindTopbarEvents();
			this.bindLeftPanelEvents();
			this.bindCanvasEvents();
			this.bindInspectorEvents();
			this.applyPageSettingsToCanvas();
			this.renderFullCanvas();
		},

		/* History (Undo / Redo) */
		pushHistory: function () {
			var snap = JSON.stringify(this.elements);
			if (this.historyIndex >= 0 && this.history[this.historyIndex] === snap) {
				return;
			}
			this.history = this.history.slice(0, this.historyIndex + 1);
			this.history.push(snap);
			if (this.history.length > 40) {
				this.history.shift();
			}
			this.historyIndex = this.history.length - 1;
			this.updateHistoryButtons();
		},

		undo: function () {
			if (this.historyIndex > 0) {
				this.historyIndex--;
				this.elements = JSON.parse(this.history[this.historyIndex]);
				this.updateHistoryButtons();
				this.renderFullCanvas();
				this.renderInspector();
			}
		},

		redo: function () {
			if (this.historyIndex < this.history.length - 1) {
				this.historyIndex++;
				this.elements = JSON.parse(this.history[this.historyIndex]);
				this.updateHistoryButtons();
				this.renderFullCanvas();
				this.renderInspector();
			}
		},

		updateHistoryButtons: function () {
			var u = document.getElementById('avb-undo-btn');
			var r = document.getElementById('avb-redo-btn');
			if (u) u.disabled = this.historyIndex <= 0;
			if (r) r.disabled = this.historyIndex >= this.history.length - 1;
		},

		/* Helpers */
		uid: function () {
			return 'avb_' + Math.random().toString(36).substring(2, 10);
		},

		cloneDefaultElement: function (type) {
			var def = CFG.widgets[type];
			if (!def) return null;
			var copy = JSON.parse(JSON.stringify(def.defaults));
			return {
				id: this.uid(),
				type: type,
				content: copy.content || {},
				style: copy.style || JSON.parse(JSON.stringify(CFG.defaultStyle)),
				advanced: copy.advanced || JSON.parse(JSON.stringify(CFG.defaultAdv)),
			};
		},

		findElement: function (id) {
			for (var i = 0; i < this.elements.length; i++) {
				if (this.elements[i].id === id) return this.elements[i];
			}
			return null;
		},

		findIndex: function (id) {
			for (var i = 0; i < this.elements.length; i++) {
				if (this.elements[i].id === id) return i;
			}
			return -1;
		},

		showToast: function (msg) {
			var t = document.getElementById('avb-toast');
			if (!t) return;
			t.textContent = msg;
			t.hidden = false;
			clearTimeout(this._toastTimer);
			this._toastTimer = setTimeout(function () {
				t.hidden = true;
			}, 2800);
		},

		/* Widget Palette */
		renderWidgetCatalog: function (query) {
			var container = document.getElementById('avb-widget-catalog');
			if (!container) return;
			var q = (query || '').toLowerCase().trim();
			var html = '';

			Object.keys(CFG.categories).forEach(function (catKey) {
				var cat = CFG.categories[catKey];
				var itemsHtml = '';

				Object.keys(CFG.widgets).forEach(function (wKey) {
					var w = CFG.widgets[wKey];
					if (w.category !== catKey) return;
					if (
						q &&
						w.title.toLowerCase().indexOf(q) === -1 &&
						w.description.toLowerCase().indexOf(q) === -1
					) {
						return;
					}

					itemsHtml +=
						'<div class="avb-widget-tile" draggable="true" data-widget-type="' +
						w.type +
						'" title="' +
						w.description +
						'">' +
						'<span class="avb-widget-tile-icon">' +
						w.icon +
						'</span>' +
						'<span class="avb-widget-tile-title">' +
						w.title +
						'</span>' +
						'</div>';
				});

				if (itemsHtml) {
					html +=
						'<div class="avb-cat-group">' +
						'<div class="avb-cat-heading"><span>' +
						cat.icon +
						'</span> ' +
						cat.title +
						'</div>' +
						'<div class="avb-widget-grid">' +
						itemsHtml +
						'</div>' +
						'</div>';
				}
			});

			container.innerHTML =
				html || '<p style="color:#9ca3af;font-size:12px;">No matching widgets found.</p>';
		},

		/* Template Library Modal */
		renderTemplateModal: function () {
			var list = document.getElementById('avb-modal-templates-list');
			if (!list) return;
			var html = '';

			Object.keys(CFG.templates).forEach(function (tKey) {
				var tpl = CFG.templates[tKey];
				html +=
					'<div class="avb-modal-tpl-card">' +
					'<div class="avb-hub-tpl-top">' +
					'<span class="avb-hub-tpl-cat">' +
					tpl.category +
					'</span>' +
					'<span class="avb-hub-tpl-badge">' +
					tpl.badge +
					'</span>' +
					'</div>' +
					'<h3>' +
					tpl.title +
					'</h3>' +
					'<p>' +
					tpl.description +
					'</p>' +
					'<div class="avb-modal-tpl-actions">' +
					'<button type="button" class="avb-modal-tpl-btn" data-tpl-action="append" data-tpl-id="' +
					tKey +
					'">+ Insert Sections</button>' +
					'<button type="button" class="avb-modal-tpl-btn is-secondary" data-tpl-action="replace" data-tpl-id="' +
					tKey +
					'">Replace Page</button>' +
					'</div>' +
					'</div>';
			});

			list.innerHTML = html;
		},

		/* Canvas Rendering */
		applyPageSettingsToCanvas: function () {
			var vp = document.getElementById('avb-canvas-viewport');
			if (vp && this.pageSettings.bg_color) {
				vp.style.backgroundColor = this.pageSettings.bg_color;
			}
		},

		renderFullCanvas: function () {
			var self = this;
			var dropzone = document.getElementById('avb-canvas-dropzone');
			var emptyState = document.getElementById('avb-empty-state');
			if (!dropzone) return;

			if (!this.elements.length) {
				dropzone.innerHTML = '';
				if (emptyState) emptyState.style.display = 'block';
				this.renderLayersList();
				return;
			}

			if (emptyState) emptyState.style.display = 'none';

			dropzone.innerHTML = '';
			this.elements.forEach(function (el) {
				var wrapper = document.createElement('div');
				wrapper.className =
					'avb-canvas-item' + (self.selectedId === el.id ? ' is-selected' : '');
				wrapper.setAttribute('data-element-id', el.id);
				wrapper.setAttribute('draggable', 'true');

				var wTitle = CFG.widgets[el.type] ? CFG.widgets[el.type].title : el.type;
				wrapper.innerHTML =
					'<div class="avb-canvas-item-bar">' +
					'<span class="avb-bar-label">⋮⋮ ' +
					wTitle +
					'</span>' +
					'<button type="button" class="avb-bar-btn" data-bar-act="up" title="Move Up">↑</button>' +
					'<button type="button" class="avb-bar-btn" data-bar-act="down" title="Move Down">↓</button>' +
					'<button type="button" class="avb-bar-btn" data-bar-act="dup" title="Duplicate">⧉</button>' +
					'<button type="button" class="avb-bar-btn is-danger" data-bar-act="del" title="Delete">✕</button>' +
					'</div>' +
					'<div class="avb-canvas-item-content" id="avb-slot-' +
					el.id +
					'">' +
					(self.htmlCache[el.id] ||
						'<div style="padding:40px;text-align:center;color:#888;">Rendering ' +
							wTitle +
							'...</div>') +
					'</div>';

				dropzone.appendChild(wrapper);

				if (!self.htmlCache[el.id]) {
					self.fetchElementHtml(el);
				}
			});

			if (window.AVBFrontend) {
				window.AVBFrontend.refresh(dropzone);
			}
			this.renderLayersList();
		},

		fetchElementHtml: function (el) {
			var self = this;
			$.post(CFG.ajaxUrl, {
				action: 'avb_render_element',
				nonce: CFG.nonce,
				element: JSON.stringify(el),
			}).done(function (res) {
				if (res && res.success && res.data && res.data.html) {
					self.htmlCache[el.id] = res.data.html;
					var slot = document.getElementById('avb-slot-' + el.id);
					if (slot) {
						slot.innerHTML = res.data.html;
						if (window.AVBFrontend) {
							window.AVBFrontend.refresh(slot);
						}
					}
				}
			});
		},

		scheduleElementRefresh: function (el) {
			var self = this;
			clearTimeout(this.renderTimer);
			this.renderTimer = setTimeout(function () {
				self.fetchElementHtml(el);
				self.pushHistory();
			}, 180);
		},

		/* Layers / Navigator */
		renderLayersList: function () {
			var self = this;
			var list = document.getElementById('avb-layers-list');
			if (!list) return;

			if (!this.elements.length) {
				list.innerHTML =
					'<li style="color:#9ca3af;font-size:12px;padding:12px 0;">No sections on canvas yet.</li>';
				return;
			}

			var html = '';
			this.elements.forEach(function (el, idx) {
				var w = CFG.widgets[el.type] || { icon: '◻', title: el.type };
				var label =
					(el.content && (el.content.title || el.content.eyebrow)) || w.title;
				if (label.length > 24) label = label.substring(0, 24) + '…';

				html +=
					'<li class="avb-layer-item ' +
					(self.selectedId === el.id ? 'is-selected' : '') +
					'" data-layer-id="' +
					el.id +
					'">' +
					'<span>' +
					w.icon +
					' ' +
					(idx + 1) +
					'. ' +
					label +
					'</span>' +
					'<div class="avb-layer-actions">' +
					'<button type="button" data-layer-act="up" title="Move Up">↑</button>' +
					'<button type="button" data-layer-act="down" title="Move Down">↓</button>' +
					'<button type="button" data-layer-act="del" title="Delete">✕</button>' +
					'</div>' +
					'</li>';
			});

			list.innerHTML = html;
		},

		/* Element Operations */
		addElement: function (type, index) {
			var el = this.cloneDefaultElement(type);
			if (!el) return;
			if (typeof index === 'number' && index >= 0 && index <= this.elements.length) {
				this.elements.splice(index, 0, el);
			} else {
				this.elements.push(el);
			}
			this.selectedId = el.id;
			this.inspectorMode = 'element';
			this.pushHistory();
			this.renderFullCanvas();
			this.renderInspector();
			this.showToast('Added ' + (CFG.widgets[type].title || type));
		},

		moveElement: function (id, dir) {
			var idx = this.findIndex(id);
			if (idx === -1) return;
			var target = idx + dir;
			if (target < 0 || target >= this.elements.length) return;
			var temp = this.elements[idx];
			this.elements[idx] = this.elements[target];
			this.elements[target] = temp;
			this.pushHistory();
			this.renderFullCanvas();
		},

		duplicateElement: function (id) {
			var idx = this.findIndex(id);
			if (idx === -1) return;
			var copy = JSON.parse(JSON.stringify(this.elements[idx]));
			copy.id = this.uid();
			if (this.htmlCache[id]) {
				this.htmlCache[copy.id] = this.htmlCache[id];
			}
			this.elements.splice(idx + 1, 0, copy);
			this.selectedId = copy.id;
			this.pushHistory();
			this.renderFullCanvas();
			this.renderInspector();
			this.showToast('Section duplicated');
		},

		deleteElement: function (id) {
			var idx = this.findIndex(id);
			if (idx === -1) return;
			this.elements.splice(idx, 1);
			delete this.htmlCache[id];
			if (this.selectedId === id) {
				this.selectedId = this.elements.length ? this.elements[0].id : null;
			}
			this.pushHistory();
			this.renderFullCanvas();
			this.renderInspector();
		},

		selectElement: function (id) {
			this.selectedId = id;
			this.inspectorMode = 'element';
			document.querySelectorAll('.avb-canvas-item').forEach(function (node) {
				node.classList.toggle(
					'is-selected',
					node.getAttribute('data-element-id') === id
				);
			});
			this.renderLayersList();
			this.renderInspector();
		},

		/* Right Inspector Rendering (Content | Style | Advanced | Page Settings) */
		renderInspector: function () {
			var titleEl = document.getElementById('avb-inspector-title');
			var tabsEl = document.getElementById('avb-inspector-tabs');
			var bodyEl = document.getElementById('avb-inspector-body');
			if (!bodyEl) return;

			if (this.inspectorMode === 'page') {
				if (titleEl) titleEl.textContent = '⚙ Page Settings';
				if (tabsEl) tabsEl.style.display = 'none';
				bodyEl.innerHTML =
					'<div class="avb-field-group">' +
					'<label class="avb-field-label">Page Layout Mode</label>' +
					'<select class="avb-select" data-page-setting="layout">' +
					'<option value="full-width" ' +
					(this.pageSettings.layout === 'full-width' ? 'selected' : '') +
					'>Atelier Full-Width (Header + Footer)</option>' +
					'<option value="canvas" ' +
					(this.pageSettings.layout === 'canvas' ? 'selected' : '') +
					'>Blank Landing Canvas (No Header/Footer)</option>' +
					'<option value="default" ' +
					(this.pageSettings.layout === 'default' ? 'selected' : '') +
					'>Theme Default Container</option>' +
					'</select>' +
					'</div>' +
					'<div class="avb-field-group">' +
					'<label class="avb-field-label">Page Canvas Background Color</label>' +
					'<div class="avb-color-row">' +
					'<input type="color" class="avb-color-picker" data-page-setting="bg_color" value="' +
					(this.pageSettings.bg_color || '#fbf9f6') +
					'" />' +
					'<input type="text" class="avb-input" data-page-setting="bg_color" value="' +
					(this.pageSettings.bg_color || '#fbf9f6') +
					'" />' +
					'</div>' +
					'</div>';
				return;
			}

			if (tabsEl) tabsEl.style.display = 'grid';

			var el = this.findElement(this.selectedId);
			if (!el) {
				if (titleEl) titleEl.textContent = 'Select an Element';
				bodyEl.innerHTML =
					'<div class="avb-inspector-placeholder"><p>Click any section on the canvas to customize its content, colors, typography, spacing, and animations.</p></div>';
				return;
			}

			var widgetDef = CFG.widgets[el.type];
			if (titleEl) {
				titleEl.textContent =
					(widgetDef ? widgetDef.icon + ' ' + widgetDef.title : el.type);
			}

			if (this.activeTab === 'content') {
				bodyEl.innerHTML = this.buildContentControls(el, widgetDef);
			} else if (this.activeTab === 'style') {
				bodyEl.innerHTML = this.buildStyleControls(el);
			} else {
				bodyEl.innerHTML = this.buildAdvancedControls(el);
			}
		},

		buildContentControls: function (el, widgetDef) {
			var self = this;
			if (!widgetDef || !Array.isArray(widgetDef.fields)) {
				return '<p>No content settings for this element.</p>';
			}

			var html = '';
			widgetDef.fields.forEach(function (f) {
				var val =
					typeof el.content[f.key] !== 'undefined' ? el.content[f.key] : '';

				if (f.type === 'repeater') {
					var list = Array.isArray(val) ? val : [];
					html +=
						'<div class="avb-field-group">' +
						'<label class="avb-field-label">' +
						f.label +
						'</label>' +
						'<div class="avb-repeater-list" data-rep-key="' +
						f.key +
						'">';

					list.forEach(function (item, idx) {
						html +=
							'<div class="avb-repeater-card" data-rep-index="' +
							idx +
							'">' +
							'<div class="avb-repeater-card-head">' +
							'<span>Item #' +
							(idx + 1) +
							'</span>' +
							'<button type="button" class="avb-repeater-del" data-rep-del="' +
							f.key +
							'" data-rep-idx="' +
							idx +
							'">Remove</button>' +
							'</div>';

						(f.subfields || []).forEach(function (sf) {
							var sval =
								typeof item[sf.key] !== 'undefined' ? item[sf.key] : '';
							html +=
								'<div class="avb-field-group" style="margin-bottom:8px;">' +
								'<label class="avb-field-label" style="font-size:10.5px;">' +
								sf.label +
								'</label>';
							if (sf.type === 'textarea') {
								html +=
									'<textarea class="avb-textarea" style="min-height:54px;" data-rep-field="' +
									f.key +
									'" data-rep-idx="' +
									idx +
									'" data-sub-key="' +
									sf.key +
									'">' +
									self.esc(sval) +
									'</textarea>';
							} else if (sf.type === 'image') {
								html +=
									'<div class="avb-media-row">' +
									'<input type="text" class="avb-input" data-rep-field="' +
									f.key +
									'" data-rep-idx="' +
									idx +
									'" data-sub-key="' +
									sf.key +
									'" value="' +
									self.esc(sval) +
									'" />' +
									'<button type="button" class="avb-media-btn" data-media-rep="' +
									f.key +
									'" data-rep-idx="' +
									idx +
									'" data-sub-key="' +
									sf.key +
									'">Media</button>' +
									'</div>';
							} else {
								html +=
									'<input type="' +
									(sf.type === 'number' ? 'number' : 'text') +
									'" class="avb-input" data-rep-field="' +
									f.key +
									'" data-rep-idx="' +
									idx +
									'" data-sub-key="' +
									sf.key +
									'" value="' +
									self.esc(sval) +
									'" />';
							}
							html += '</div>';
						});

						html += '</div>';
					});

					html +=
						'</div>' +
						'<button type="button" class="avb-repeater-add" data-rep-add="' +
						f.key +
						'">+ Add ' +
						f.label +
						' Item</button>' +
						'</div>';
					return;
				}

				html += '<div class="avb-field-group">';
				html += '<label class="avb-field-label">' + f.label + '</label>';

				if (f.type === 'textarea') {
					html +=
						'<textarea class="avb-textarea" data-scope="content" data-key="' +
						f.key +
						'">' +
						self.esc(val) +
						'</textarea>';
				} else if (f.type === 'select') {
					html +=
						'<select class="avb-select" data-scope="content" data-key="' +
						f.key +
						'">';
					Object.keys(f.options || {}).forEach(function (optVal) {
						html +=
							'<option value="' +
							optVal +
							'" ' +
							(String(val) === String(optVal) ? 'selected' : '') +
							'>' +
							f.options[optVal] +
							'</option>';
					});
					html += '</select>';
				} else if (f.type === 'toggle') {
					html +=
						'<label style="display:flex;align-items:center;gap:8px;font-size:12px;cursor:pointer;">' +
						'<input type="checkbox" data-scope="content" data-key="' +
						f.key +
						'" ' +
						(val ? 'checked' : '') +
						' /> Enable' +
						'</label>';
				} else if (f.type === 'image') {
					html +=
						'<div class="avb-media-row">' +
						'<input type="text" class="avb-input" data-scope="content" data-key="' +
						f.key +
						'" value="' +
						self.esc(val) +
						'" />' +
						'<button type="button" class="avb-media-btn" data-media-target="content:' +
						f.key +
						'">Upload</button>' +
						'</div>';
				} else if (f.type === 'number') {
					html +=
						'<input type="number" class="avb-input" data-scope="content" data-key="' +
						f.key +
						'" value="' +
						self.esc(val) +
						'" ' +
						(typeof f.min !== 'undefined' ? 'min="' + f.min + '"' : '') +
						' ' +
						(typeof f.max !== 'undefined' ? 'max="' + f.max + '"' : '') +
						' />';
				} else {
					html +=
						'<input type="text" class="avb-input" data-scope="content" data-key="' +
						f.key +
						'" value="' +
						self.esc(val) +
						'" />';
				}

				html += '</div>';
			});

			return html;
		},

		buildStyleControls: function (el) {
			var s = el.style || {};
			return (
				'<div class="avb-field-group">' +
				'<label class="avb-field-label">Text Alignment</label>' +
				'<select class="avb-select" data-scope="style" data-key="text_align">' +
				'<option value="left" ' +
				(s.text_align === 'left' ? 'selected' : '') +
				'>Left</option>' +
				'<option value="center" ' +
				(s.text_align === 'center' ? 'selected' : '') +
				'>Center</option>' +
				'<option value="right" ' +
				(s.text_align === 'right' ? 'selected' : '') +
				'>Right</option>' +
				'</select>' +
				'</div>' +
				'<div class="avb-field-group">' +
				'<label class="avb-field-label">Heading Typography Scale</label>' +
				'<select class="avb-select" data-scope="style" data-key="typo_scale">' +
				'<option value="small" ' +
				(s.typo_scale === 'small' ? 'selected' : '') +
				'>Compact (Small)</option>' +
				'<option value="normal" ' +
				(s.typo_scale === 'normal' ? 'selected' : '') +
				'>Editorial Default</option>' +
				'<option value="large" ' +
				(s.typo_scale === 'large' ? 'selected' : '') +
				'>Large Exhibition</option>' +
				'<option value="xl" ' +
				(s.typo_scale === 'xl' ? 'selected' : '') +
				'>Display Oversized (XL)</option>' +
				'</select>' +
				'</div>' +
				this.colorField('Background Color', 'bg_color', s.bg_color || '') +
				'<div class="avb-field-group">' +
				'<label class="avb-field-label">Background Image URL</label>' +
				'<div class="avb-media-row">' +
				'<input type="text" class="avb-input" data-scope="style" data-key="bg_image" value="' +
				this.esc(s.bg_image || '') +
				'" />' +
				'<button type="button" class="avb-media-btn" data-media-target="style:bg_image">Upload</button>' +
				'</div>' +
				'</div>' +
				'<div class="avb-field-group">' +
				'<label class="avb-field-label">Background Dark Overlay Opacity (0 - 90%)</label>' +
				'<input type="number" class="avb-input" data-scope="style" data-key="bg_overlay" min="0" max="90" value="' +
				(s.bg_overlay || 0) +
				'" />' +
				'</div>' +
				this.colorField('Heading Color', 'heading_color', s.heading_color || '') +
				this.colorField('Body Text Color', 'text_color', s.text_color || '') +
				this.colorField('Accent & Button Color', 'accent_color', s.accent_color || '#b47b56') +
				'<div class="avb-spacing-grid">' +
				'<div class="avb-field-group">' +
				'<label class="avb-field-label">Corner Radius (px)</label>' +
				'<input type="number" class="avb-input" data-scope="style" data-key="border_radius" min="0" max="60" value="' +
				(s.border_radius || 0) +
				'" />' +
				'</div>' +
				'<div class="avb-field-group">' +
				'<label class="avb-field-label">Box Shadow</label>' +
				'<select class="avb-select" data-scope="style" data-key="box_shadow">' +
				'<option value="none" ' +
				(s.box_shadow === 'none' ? 'selected' : '') +
				'>None</option>' +
				'<option value="subtle" ' +
				(s.box_shadow === 'subtle' ? 'selected' : '') +
				'>Subtle Soft</option>' +
				'<option value="elevated" ' +
				(s.box_shadow === 'elevated' ? 'selected' : '') +
				'>Elevated</option>' +
				'<option value="luxury" ' +
				(s.box_shadow === 'luxury' ? 'selected' : '') +
				'>Deep Luxury</option>' +
				'</select>' +
				'</div>' +
				'</div>' +
				'<div class="avb-spacing-grid">' +
				'<div class="avb-field-group">' +
				'<label class="avb-field-label">Border Style</label>' +
				'<select class="avb-select" data-scope="style" data-key="border_style">' +
				'<option value="none" ' +
				(s.border_style === 'none' ? 'selected' : '') +
				'>None</option>' +
				'<option value="solid" ' +
				(s.border_style === 'solid' ? 'selected' : '') +
				'>Solid 1px</option>' +
				'<option value="dashed" ' +
				(s.border_style === 'dashed' ? 'selected' : '') +
				'>Dashed</option>' +
				'</select>' +
				'</div>' +
				'<div class="avb-field-group">' +
				'<label class="avb-field-label">Border Color</label>' +
				'<input type="text" class="avb-input" data-scope="style" data-key="border_color" value="' +
				this.esc(s.border_color || '#e6e1da') +
				'" />' +
				'</div>' +
				'</div>'
			);
		},

		buildAdvancedControls: function (el) {
			var a = el.advanced || {};
			return (
				'<div class="avb-field-group">' +
				'<label class="avb-field-label">Content Container Width</label>' +
				'<select class="avb-select" data-scope="advanced" data-key="container_width">' +
				'<option value="boxed" ' +
				(a.container_width === 'boxed' ? 'selected' : '') +
				'>Boxed Editorial (960px)</option>' +
				'<option value="wide" ' +
				(a.container_width === 'wide' ? 'selected' : '') +
				'>Wide Storefront (1320px)</option>' +
				'<option value="full" ' +
				(a.container_width === 'full' ? 'selected' : '') +
				'>Full Bleed Edge-to-Edge (100%)</option>' +
				'</select>' +
				'</div>' +
				'<label class="avb-field-label">Section Padding (px)</label>' +
				'<div class="avb-spacing-grid">' +
				'<div class="avb-field-group"><label class="avb-field-label">Top</label><input type="number" class="avb-input" data-scope="advanced" data-key="padding_top" value="' +
				(a.padding_top ?? 64) +
				'" /></div>' +
				'<div class="avb-field-group"><label class="avb-field-label">Bottom</label><input type="number" class="avb-input" data-scope="advanced" data-key="padding_bottom" value="' +
				(a.padding_bottom ?? 64) +
				'" /></div>' +
				'<div class="avb-field-group"><label class="avb-field-label">Left</label><input type="number" class="avb-input" data-scope="advanced" data-key="padding_left" value="' +
				(a.padding_left ?? 24) +
				'" /></div>' +
				'<div class="avb-field-group"><label class="avb-field-label">Right</label><input type="number" class="avb-input" data-scope="advanced" data-key="padding_right" value="' +
				(a.padding_right ?? 24) +
				'" /></div>' +
				'</div>' +
				'<label class="avb-field-label">Section Margin (px)</label>' +
				'<div class="avb-spacing-grid">' +
				'<div class="avb-field-group"><label class="avb-field-label">Margin Top</label><input type="number" class="avb-input" data-scope="advanced" data-key="margin_top" value="' +
				(a.margin_top ?? 0) +
				'" /></div>' +
				'<div class="avb-field-group"><label class="avb-field-label">Margin Bottom</label><input type="number" class="avb-input" data-scope="advanced" data-key="margin_bottom" value="' +
				(a.margin_bottom ?? 0) +
				'" /></div>' +
				'</div>' +
				'<div class="avb-field-group">' +
				'<label class="avb-field-label">Entrance Animation</label>' +
				'<select class="avb-select" data-scope="advanced" data-key="animation">' +
				'<option value="none" ' +
				(a.animation === 'none' ? 'selected' : '') +
				'>None</option>' +
				'<option value="fade-up" ' +
				(a.animation === 'fade-up' ? 'selected' : '') +
				'>Fade Up</option>' +
				'<option value="fade-in" ' +
				(a.animation === 'fade-in' ? 'selected' : '') +
				'>Fade In</option>' +
				'<option value="zoom-in" ' +
				(a.animation === 'zoom-in' ? 'selected' : '') +
				'>Gentle Scale Zoom</option>' +
				'</select>' +
				'</div>' +
				'<div class="avb-field-group">' +
				'<label class="avb-field-label">Responsive Visibility</label>' +
				'<label style="display:flex;gap:8px;margin-bottom:6px;font-size:12px;"><input type="checkbox" data-scope="advanced" data-key="hide_desktop" ' +
				(a.hide_desktop ? 'checked' : '') +
				' /> Hide on Desktop</label>' +
				'<label style="display:flex;gap:8px;margin-bottom:6px;font-size:12px;"><input type="checkbox" data-scope="advanced" data-key="hide_tablet" ' +
				(a.hide_tablet ? 'checked' : '') +
				' /> Hide on Tablet</label>' +
				'<label style="display:flex;gap:8px;font-size:12px;"><input type="checkbox" data-scope="advanced" data-key="hide_mobile" ' +
				(a.hide_mobile ? 'checked' : '') +
				' /> Hide on Mobile</label>' +
				'</div>' +
				'<div class="avb-field-group">' +
				'<label class="avb-field-label">Custom Anchor ID</label>' +
				'<input type="text" class="avb-input" data-scope="advanced" data-key="custom_id" placeholder="e.g. story" value="' +
				this.esc(a.custom_id || '') +
				'" />' +
				'</div>' +
				'<div class="avb-field-group">' +
				'<label class="avb-field-label">Custom CSS Class</label>' +
				'<input type="text" class="avb-input" data-scope="advanced" data-key="custom_class" value="' +
				this.esc(a.custom_class || '') +
				'" />' +
				'</div>'
			);
		},

		colorField: function (label, key, val) {
			var pickerVal = /^#[0-9a-fA-F]{6}$/.test(val) ? val : '#161514';
			return (
				'<div class="avb-field-group">' +
				'<label class="avb-field-label">' +
				label +
				'</label>' +
				'<div class="avb-color-row">' +
				'<input type="color" class="avb-color-picker" data-color-sync="' +
				key +
				'" value="' +
				pickerVal +
				'" />' +
				'<input type="text" class="avb-input" data-scope="style" data-key="' +
				key +
				'" placeholder="Default / #hex" value="' +
				this.esc(val) +
				'" />' +
				'</div>' +
				'</div>'
			);
		},

		esc: function (str) {
			return String(str === null || typeof str === 'undefined' ? '' : str)
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;');
		},

		/* Event Bindings */
		bindTopbarEvents: function () {
			var self = this;

			// Viewport Switcher
			document.querySelectorAll('.avb-vp-btn').forEach(function (btn) {
				btn.addEventListener('click', function () {
					var vp = btn.getAttribute('data-viewport');
					document.querySelectorAll('.avb-vp-btn').forEach(function (b) {
						b.classList.toggle('is-active', b === btn);
					});
					var stage = document.getElementById('avb-canvas-viewport');
					if (stage) {
						stage.className = 'avb-canvas-viewport is-' + vp;
					}
				});
			});

			// Undo / Redo
			var undoBtn = document.getElementById('avb-undo-btn');
			var redoBtn = document.getElementById('avb-redo-btn');
			if (undoBtn)
				undoBtn.addEventListener('click', function () {
					self.undo();
				});
			if (redoBtn)
				redoBtn.addEventListener('click', function () {
					self.redo();
				});

			document.addEventListener('keydown', function (e) {
				if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z') {
					if (
						document.activeElement &&
						(document.activeElement.tagName === 'INPUT' ||
							document.activeElement.tagName === 'TEXTAREA')
					) {
						return;
					}
					e.preventDefault();
					if (e.shiftKey) self.redo();
					else self.undo();
				}
			});

			// Templates Modal
			var modal = document.getElementById('avb-templates-modal');
			var openTpl = document.getElementById('avb-open-templates-btn');
			var emptyOpenTpl = document.getElementById('avb-empty-open-templates');
			var closeTpl = document.getElementById('avb-close-templates-btn');

			function showModal() {
				if (modal) modal.hidden = false;
			}
			function hideModal() {
				if (modal) modal.hidden = true;
			}

			if (openTpl) openTpl.addEventListener('click', showModal);
			if (emptyOpenTpl) emptyOpenTpl.addEventListener('click', showModal);
			if (closeTpl) closeTpl.addEventListener('click', hideModal);

			if (modal) {
				modal.addEventListener('click', function (e) {
					if (e.target.classList.contains('avb-modal-backdrop')) hideModal();
					var actBtn = e.target.closest('[data-tpl-action]');
					if (!actBtn) return;
					var tplId = actBtn.getAttribute('data-tpl-id');
					var mode = actBtn.getAttribute('data-tpl-action');
					var tpl = CFG.templates[tplId];
					if (!tpl || !Array.isArray(tpl.elements)) return;

					var cloned = JSON.parse(JSON.stringify(tpl.elements)).map(function (item) {
						item.id = self.uid();
						return item;
					});

					if (mode === 'replace') {
						self.elements = cloned;
					} else {
						self.elements = self.elements.concat(cloned);
					}
					self.selectedId = cloned.length ? cloned[0].id : null;
					self.pushHistory();
					self.renderFullCanvas();
					self.renderInspector();
					hideModal();
					self.showToast('Loaded template: ' + tpl.title);
				});
			}

			// Page Settings
			var pageSetBtn = document.getElementById('avb-page-settings-btn');
			if (pageSetBtn) {
				pageSetBtn.addEventListener('click', function () {
					self.inspectorMode = 'page';
					self.renderInspector();
				});
			}

			// Save & Publish
			var saveBtn = document.getElementById('avb-save-btn');
			if (saveBtn) {
				saveBtn.addEventListener('click', function () {
					self.savePage(saveBtn);
				});
			}
		},

		bindLeftPanelEvents: function () {
			var self = this;

			// Left Tabs (Elements vs Navigator)
			document.querySelectorAll('.avb-left-tab').forEach(function (tab) {
				tab.addEventListener('click', function () {
					var target = tab.getAttribute('data-left-tab');
					document.querySelectorAll('.avb-left-tab').forEach(function (t) {
						t.classList.toggle('is-active', t === tab);
					});
					document
						.getElementById('avb-left-widgets')
						.classList.toggle('is-active', target === 'widgets');
					document
						.getElementById('avb-left-layers')
						.classList.toggle('is-active', target === 'layers');
				});
			});

			// Search Widgets
			var searchInput = document.getElementById('avb-widget-search');
			if (searchInput) {
				searchInput.addEventListener('input', function () {
					self.renderWidgetCatalog(searchInput.value);
				});
			}

			// Click or Drag Widget Tile
			var catalog = document.getElementById('avb-widget-catalog');
			if (catalog) {
				catalog.addEventListener('click', function (e) {
					var tile = e.target.closest('.avb-widget-tile');
					if (!tile) return;
					self.addElement(tile.getAttribute('data-widget-type'));
				});

				catalog.addEventListener('dragstart', function (e) {
					var tile = e.target.closest('.avb-widget-tile');
					if (!tile) return;
					self.draggedWidgetType = tile.getAttribute('data-widget-type');
					self.draggedElementId = null;
					e.dataTransfer.setData('text/plain', self.draggedWidgetType);
				});
			}

			// Clear All Button
			var clearBtn = document.getElementById('avb-clear-canvas-btn');
			if (clearBtn) {
				clearBtn.addEventListener('click', function () {
					if (!self.elements.length) return;
					if (confirm('Remove all sections from this page?')) {
						self.elements = [];
						self.selectedId = null;
						self.pushHistory();
						self.renderFullCanvas();
						self.renderInspector();
					}
				});
			}

			// Navigator Layer Actions
			var layersList = document.getElementById('avb-layers-list');
			if (layersList) {
				layersList.addEventListener('click', function (e) {
					var item = e.target.closest('.avb-layer-item');
					if (!item) return;
					var id = item.getAttribute('data-layer-id');
					var actBtn = e.target.closest('[data-layer-act]');
					if (actBtn) {
						e.stopPropagation();
						var act = actBtn.getAttribute('data-layer-act');
						if (act === 'up') self.moveElement(id, -1);
						else if (act === 'down') self.moveElement(id, 1);
						else if (act === 'del') self.deleteElement(id);
						return;
					}
					self.selectElement(id);
					var domEl = document.querySelector(
						'.avb-canvas-item[data-element-id="' + id + '"]'
					);
					if (domEl) domEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
				});
			}
		},

		bindCanvasEvents: function () {
			var self = this;
			var viewport = document.getElementById('avb-canvas-viewport');
			var dropzone = document.getElementById('avb-canvas-dropzone');
			if (!viewport || !dropzone) return;

			// Click to select or toolbar action
			dropzone.addEventListener('click', function (e) {
				var barBtn = e.target.closest('[data-bar-act]');
				var itemNode = e.target.closest('.avb-canvas-item');
				if (!itemNode) return;
				var id = itemNode.getAttribute('data-element-id');

				if (barBtn) {
					e.preventDefault();
					e.stopPropagation();
					var act = barBtn.getAttribute('data-bar-act');
					if (act === 'up') self.moveElement(id, -1);
					else if (act === 'down') self.moveElement(id, 1);
					else if (act === 'dup') self.duplicateElement(id);
					else if (act === 'del') self.deleteElement(id);
					return;
				}

				// Prevent links inside canvas from navigating away while editing
				if (e.target.closest('a')) {
					e.preventDefault();
				}

				self.selectElement(id);
			});

			// Drag & Drop sorting and inserting on Canvas
			dropzone.addEventListener('dragstart', function (e) {
				var itemNode = e.target.closest('.avb-canvas-item');
				if (!itemNode) return;
				self.draggedElementId = itemNode.getAttribute('data-element-id');
				self.draggedWidgetType = null;
				e.dataTransfer.setData('text/plain', self.draggedElementId);
			});

			viewport.addEventListener('dragover', function (e) {
				e.preventDefault();
			});

			viewport.addEventListener('drop', function (e) {
				e.preventDefault();
				var targetItem = e.target.closest('.avb-canvas-item');
				var targetIdx = targetItem
					? self.findIndex(targetItem.getAttribute('data-element-id'))
					: self.elements.length;

				if (self.draggedWidgetType) {
					self.addElement(self.draggedWidgetType, targetIdx);
					self.draggedWidgetType = null;
				} else if (self.draggedElementId) {
					var fromIdx = self.findIndex(self.draggedElementId);
					if (fromIdx !== -1 && targetIdx !== -1 && fromIdx !== targetIdx) {
						var moved = self.elements.splice(fromIdx, 1)[0];
						self.elements.splice(targetIdx, 0, moved);
						self.pushHistory();
						self.renderFullCanvas();
					}
					self.draggedElementId = null;
				}
			});
		},

		bindInspectorEvents: function () {
			var self = this;

			// Inspector Tabs (Content | Style | Advanced)
			document.querySelectorAll('.avb-insp-tab').forEach(function (tab) {
				tab.addEventListener('click', function () {
					self.inspectorMode = 'element';
					self.activeTab = tab.getAttribute('data-insp-tab');
					document.querySelectorAll('.avb-insp-tab').forEach(function (t) {
						t.classList.toggle('is-active', t === tab);
					});
					self.renderInspector();
				});
			});

			var bodyEl = document.getElementById('avb-inspector-body');
			if (!bodyEl) return;

			// Field value changes
			bodyEl.addEventListener('input', function (e) {
				var target = e.target;

				// Page settings
				if (target.hasAttribute('data-page-setting')) {
					var pKey = target.getAttribute('data-page-setting');
					self.pageSettings[pKey] = target.value;
					self.applyPageSettingsToCanvas();
					return;
				}

				var el = self.findElement(self.selectedId);
				if (!el) return;

				// Color Picker sync
				if (target.hasAttribute('data-color-sync')) {
					var cKey = target.getAttribute('data-color-sync');
					el.style[cKey] = target.value;
					var textInput = bodyEl.querySelector(
						'[data-scope="style"][data-key="' + cKey + '"]'
					);
					if (textInput) textInput.value = target.value;
					self.scheduleElementRefresh(el);
					return;
				}

				// Repeater subfield input
				if (target.hasAttribute('data-rep-field')) {
					var rKey = target.getAttribute('data-rep-field');
					var rIdx = parseInt(target.getAttribute('data-rep-idx'), 10);
					var sKey = target.getAttribute('data-sub-key');
					if (Array.isArray(el.content[rKey]) && el.content[rKey][rIdx]) {
						el.content[rKey][rIdx][sKey] = target.value;
						self.scheduleElementRefresh(el);
					}
					return;
				}

				// Standard Content / Style / Advanced field
				var scope = target.getAttribute('data-scope');
				var key = target.getAttribute('data-key');
				if (!scope || !key) return;

				var val =
					target.type === 'checkbox'
						? target.checked
						: target.type === 'number'
						? Number(target.value)
						: target.value;

				el[scope][key] = val;
				self.scheduleElementRefresh(el);
			});

			// Clicks inside Inspector (Repeater Add/Remove + WP Media Library Picker)
			bodyEl.addEventListener('click', function (e) {
				var el = self.findElement(self.selectedId);
				if (!el) return;

				// Repeater Add
				var addBtn = e.target.closest('[data-rep-add]');
				if (addBtn) {
					var rKey = addBtn.getAttribute('data-rep-add');
					if (!Array.isArray(el.content[rKey])) el.content[rKey] = [];
					var sample = el.content[rKey].length
						? JSON.parse(
								JSON.stringify(el.content[rKey][el.content[rKey].length - 1])
						  )
						: { title: 'New Item', text: 'Description...' };
					el.content[rKey].push(sample);
					self.renderInspector();
					self.scheduleElementRefresh(el);
					return;
				}

				// Repeater Delete
				var delBtn = e.target.closest('[data-rep-del]');
				if (delBtn) {
					var dKey = delBtn.getAttribute('data-rep-del');
					var dIdx = parseInt(delBtn.getAttribute('data-rep-idx'), 10);
					if (Array.isArray(el.content[dKey])) {
						el.content[dKey].splice(dIdx, 1);
						self.renderInspector();
						self.scheduleElementRefresh(el);
					}
					return;
				}

				// Media Uploader for Standard Field
				var mediaBtn = e.target.closest('[data-media-target]');
				if (mediaBtn && window.wp && window.wp.media) {
					var parts = mediaBtn.getAttribute('data-media-target').split(':');
					var frame = window.wp.media({
						title: 'Select or Upload Image',
						button: { text: 'Use Image' },
						multiple: false,
					});
					frame.on('select', function () {
						var att = frame.state().get('selection').first().toJSON();
						if (att && att.url) {
							el[parts[0]][parts[1]] = att.url;
							self.renderInspector();
							self.scheduleElementRefresh(el);
						}
					});
					frame.open();
					return;
				}

				// Media Uploader for Repeater Field
				var repMediaBtn = e.target.closest('[data-media-rep]');
				if (repMediaBtn && window.wp && window.wp.media) {
					var mrKey = repMediaBtn.getAttribute('data-media-rep');
					var mrIdx = parseInt(repMediaBtn.getAttribute('data-rep-idx'), 10);
					var msKey = repMediaBtn.getAttribute('data-sub-key');
					var rFrame = window.wp.media({
						title: 'Select or Upload Image',
						button: { text: 'Use Image' },
						multiple: false,
					});
					rFrame.on('select', function () {
						var att = rFrame.state().get('selection').first().toJSON();
						if (
							att &&
							att.url &&
							Array.isArray(el.content[mrKey]) &&
							el.content[mrKey][mrIdx]
						) {
							el.content[mrKey][mrIdx][msKey] = att.url;
							self.renderInspector();
							self.scheduleElementRefresh(el);
						}
					});
					rFrame.open();
				}
			});
		},

		/* Save & Publish Page */
		savePage: function (btnEl) {
			var self = this;
			var origText = btnEl.textContent;
			btnEl.disabled = true;
			btnEl.textContent = 'Saving...';

			var titleInput = document.getElementById('avb-page-title-input');
			var statusSelect = document.getElementById('avb-post-status-select');

			$.post(CFG.ajaxUrl, {
				action: 'avb_save_page',
				nonce: CFG.nonce,
				post_id: CFG.postId,
				post_title: titleInput ? titleInput.value : CFG.postTitle,
				post_status: statusSelect ? statusSelect.value : 'publish',
				elements: JSON.stringify(this.elements),
				page_settings: JSON.stringify(this.pageSettings),
			})
				.done(function (res) {
					if (res && res.success) {
						self.showToast('✓ Page saved & published!');
						if (res.data && res.data.permalink) {
							var prev = document.getElementById('avb-preview-link');
							if (prev) prev.href = res.data.permalink;
						}
					} else {
						self.showToast('Error saving page.');
					}
				})
				.always(function () {
					btnEl.disabled = false;
					btnEl.textContent = origText;
				});
		},
	};

	$(document).ready(function () {
		Studio.init();
	});
})(jQuery);
