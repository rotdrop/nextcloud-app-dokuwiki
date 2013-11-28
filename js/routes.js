$(document).ready(function(){
    OC.Router.registerLoadedCallback(function(){
        var url = OC.Router.generate('dokuwikirefresh');
        setInterval(function(){
            $.post(url);
        }, 300000);
    });
});
