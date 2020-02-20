wp.media.view.AstraSearch = require( './search.js' );

var AstraContent = wp.media.View.extend({

    tagName: 'div',
    className: 'ast-attachments-search-wrap',
    initialize: function() {
        this.value = this.options.value;
    },

    render: function() {

        var search = new wp.media.view.AstraSearch({
            controller: this.controller,
            model: this.model,
        });
        this.views.add( search );
        return this;
    }
});

module.exports = AstraContent;