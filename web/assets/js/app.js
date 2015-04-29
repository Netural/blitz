$(document).ready(function(){
	
	var $editor = $('#previewmode-editor');
	var App = {
		$nestableWrapper: Hogan.compile($('#nestable-wrapper').html()),
		$nestableItem: Hogan.compile($('#nestable-item').html()),
		data: null,
		items: '',
		$iframeContainer: null,
		$iframe: null,
		formWidth: 360,
		siteUrl: $editor.data('site-url'),
		currentUrl: document.URL,
		remoteUrl: $editor.data('remote-url'),
		init: function() {
			var that = this;
			$.ajax({
				url: this.remoteUrl,
				type: 'GET',
				dataType: 'json',
				success: function(data){
					that.data = data
					that.renderFields();
				}
			});

			this.loadIframe();

			$('[data-save]').click(function(e){
				e.preventDefault();
				that.save();
			});

			$('[data-publish]').click(function(e){
				e.preventDefault();
				that.save('publish');
			});

			$(document).on('change', '[data-input]', function() {
				that.save();
			});

			$(document).on('focus', '[data-input]', function() {
				$(this).height(this.scrollHeight - 8);
			});

			$(document).on('blur', '[data-input]', function() {
				$(this).height('auto');
			});

			$(document).on('click', '[data-add-btn]', function(e) {
				e.preventDefault();
				var $list = $(this).closest('.uk-nestable-item').next();
				var $listItemCopy = $list.find('> .uk-nestable-list-item:first-child').clone();
				var listItems = $list.find('> li').length;

				$listItemCopy.find('[data-input]').each(function(e) {
					var oldName = $(this).attr('name');
					$(this).attr('name', oldName.replace('[0]', '['+listItems+']'));
				});

				$list.append($listItemCopy);

				that.save();
			});

			$(document).on('click', '[data-remove-btn]', function(e) {
				e.preventDefault();
				var list = $(this).closest('.uk-nestable-list');
				$(this).closest('li').remove();
				that.reindex(list);
				that.save();
			});
		},
		reindex: function(list) {
			list.find('> li').each(function(index) {
				var mask = $(this).find('> ul').data('mask');
				$(this).find('[data-input]').each(function() {
					$(this).attr('name', mask.replace('#', index) + '['+$(this).data('key')+']');
				});
			});
		},
		loadIframe: function() {
			this.$iframeContainer = $('<div id="previewmode-iframe-container" />').appendTo('#site');
			this.$iframe = $('<iframe id="previewmode-iframe" frameborder="0" />').appendTo(this.$iframeContainer);
			this.$iframeContainer.css('left', this.formWidth);
			this.setIframeWidth();
			this.$iframe.attr('src', this.siteUrl);
			/*
			For local files
			var scrollTop = $(this.$iframe[0].contentWindow.document).scrollTop();
			$.get(this.siteUrl, $.proxy(function(response) {
				var html = response +
				'<script type="text/javascript">document.body.scrollTop = '+scrollTop+';</script>';

				this.$iframe.css('background', $(this.$iframe[0].contentWindow.document.body).css('background'));
				this.$iframe[0].contentWindow.document.open();
				this.$iframe[0].contentWindow.document.write(html);
				this.$iframe[0].contentWindow.document.close();

			}, this));
			*/
		},
		reloadIframe: function() {
			this.$iframe.attr('src', this.siteUrl);
		},
		save: function(mode) {
			var that = this;

			$.ajax({
				url: that.currentUrl + (mode == 'publish' ? '/publish' : '/save'),
				type: 'POST',
				data: $('#blitz-form').serialize(),
				dataType: 'json',
				success: function(data){
					$('[data-flash-message]').html(data.message);
					that.reloadIframe();
				}
			});
		},
		setIframeWidth: function()
		{
			this.$iframeContainer.width($(window).width()-this.formWidth);
		},
		renderFields: function() {
			var items = this.renderItems(this.data, 'data');
			var html = this.$nestableWrapper.render({nestables: items});
			$('#nestables').append(html);

			var pagination = UIkit.nestable($('[data-uk-nestable]'), {});
		},
		renderItems: function(data, name) {
			var items = '';
			var that = this;
			$.each(data, function(key, val) {
				childs = '';
				var value = val;
				var humanKey = key;
				var inputname = name + '[' + key + ']';
				var isarray = $.isArray(val);
				var isitem = false;
				var mask = '';

				if (typeof key === 'string') {
					if (key.indexOf(':protected') != -1) {
						return;
					}
					humanKey = humanKey.replace(/_/g, ' ');
					humanKey = Humanize.capitalizeAll(humanKey);
				}
				if (typeof key === 'number') {
					humanKey = 'Item';
					isitem = true;
					mask = name + '[#]';
				}
				if (typeof val === 'object') {
					childs += '<ul data-mask="'+mask+'">';
					childs += that.renderItems(val, inputname);
					childs += '</ul>';
					value = false;
				}

				items += that.$nestableItem.render({isitem: isitem, name: inputname, key: key, humanKey: humanKey, val: value, childs: childs, isarray: isarray});
			});
			return items;
		},
	}

	App.init();

});