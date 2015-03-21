/**
 * backend.js
 *
 * @author Rohil Mistry
 * @package CF7 Success Page Search with AJAX
 * @version 1.1.1
 */
 
//There is no use of request variable here, you can delete it 
var request	=	jQuery("#cf7-s").val(),
	ajax_url		=	cf7_redirect_params.admin_ajax_url,
	loader_icon		=	cf7_redirect_params.loading,
	el = jQuery('.cf7-s');

el.autocomplete({
	minChars        : '3', //Change it to trigger autosuggest. Default is 1 character
	serviceUrl      : ajax_url + '?action=find_searched_page',
	onSearchStart   : function () {
		jQuery(this).css('background', 'url(' + loader_icon + ') no-repeat right center');
	},
	onSearchComplete: function (query, suggestions) {
		jQuery(this).css('background', '');
	},
	onSelect        : function (suggestion) {
		if (suggestion.id != -1) {
			
			console.log(suggestion.value); //For debugging purpose only
			
			jQuery("#cf7").val(suggestion.id);
		}
	}
});
