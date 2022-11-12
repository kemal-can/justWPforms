(function ($, _, Backbone, api, settings) {

	justwpforms.classes.models.parts.title = justwpforms.classes.models.Part.extend({
		defaults: function () {
			return _.extend(
				{},
				settings.formParts.title.defaults,
				_.result(justwpforms.classes.models.Part.prototype, 'defaults'),
			);
		},
	});

	justwpforms.classes.views.parts.title = justwpforms.classes.views.Part.extend({
		template: '#justwpforms-customize-title-template',

		initialize: function () {
			justwpforms.classes.views.Part.prototype.initialize.apply(this, arguments);

			this.listenTo(this.model, 'change:required', this.onRequiredChange);
		},

		onRequiredChange: function( model, value ) {
			model.fetchHtml(function (response) {
				var data = {
				id: model.get('id'),
				html: response,
				};

				justwpforms.previewSend('justwpforms-form-part-refresh', data);
			});
		}
	});

})(jQuery, _, Backbone, wp.customize, _justwpformsSettings);
