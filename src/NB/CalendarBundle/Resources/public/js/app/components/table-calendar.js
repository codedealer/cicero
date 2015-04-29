define(function(require){
	var TableComponent,
        BaseComponent = require('oroui/js/app/components/base/component');

    TableComponent = BaseComponent.extend({
    	initialize: function(options){
    		this.$elem = options._sourceElement;
    		this.options = options;
    		TableComponent.__super__.initialize.call(this, options);
    	}
    });

    return TableComponent;
});