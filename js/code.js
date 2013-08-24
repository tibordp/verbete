$('#verbeInput').typeahead({
    name: 'verbs',
    prefetch: 'data/verbes.json',
    remote: 'data/verbes.json?q=%QUERY',
    limit: 10
});

$('.typeahead').backgroundColor = '#fff';

make = function() {
    params = { verbe :  verb };
    
    if ($("#neg-toggle").hasClass('active'))
        params = $.extend(params, { negative : 1 });
    if ($("#ref-toggle").hasClass('active'))
        params = $.extend(params, { reflexive : 1 });
    
    return $.param(params);
};

jQuery.fn.extend({
    buttonToggle: function() {
        if (this.hasClass('active'))
            this.removeClass('active');
        else
            this.addClass('active');
        return this;
    }});

$("#neg-toggle,#ref-toggle").click(function(e) {
    $(this).buttonToggle();
    location.href = '?' + make();
});