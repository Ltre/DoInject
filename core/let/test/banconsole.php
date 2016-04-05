<script>
(function(){
    try {
        var $_console$$ = console;
        Object.defineProperty(window, "console", {
            get: function() {
                if ($_console$$._commandLineAPI)
                    throw "Sorry, for security reasons, the script console is deactivated on this site.";
                return $_console$$;
            },
            set: function($val$_$) {
                $_console$$ = $val$_$;
            }
        });
    } catch ($ignore$$) {
    }
})();
</script>