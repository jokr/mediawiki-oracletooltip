var cards = jQuery('.mtg-card');
cards.click(function () {
    agent = navigator.userAgent;
    windowName = "Sitelet";
    params  = "";
    params += "toolbar=1,";
    params += "location=1,";
    params += "directories=0,";
    params += "status=0,";
    params += "menubar=0,";
    params += "scrollbars=1,";
    params += "resizable=1,";
    params += "width=800,";
    params += "height=670";

    var cardName = jQuery(this).html();
    // TODO implement search for split cards

    window.open("http://gatherer.wizards.com/Pages/Card/Details.aspx?name="+cardName, windowName, params);
});

cards.tooltip(
    {
        items:'.mtg-card',
        tooltipClass:'oracle-tooltip',
        position: {my: "left top+5", at: "left bottom", collision: "flipfit"},
        content: function(callback) {
            var cardname = jQuery(this).html();
            var url = jQuery(this).attr("src");
            jQuery.get(url, {
                name: cardname
            }, function(data) {
                callback(data);
            });
        }
    }
);
