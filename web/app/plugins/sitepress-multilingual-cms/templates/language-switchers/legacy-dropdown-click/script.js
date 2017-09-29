/*jshint browser:true, devel:true */
/*global document */

var WPMLLanguageSwitcherDropdownClick = (function() {
    "use strict";

    var isOpen = false;

    var toggle = function(switcher) {

        var subMenu;

        if (switcher !== undefined) {
            subMenu = switcher.getElementsByClassName('wpml-ls-sub-menu')[0];
        }

        if(subMenu.style.visibility === 'visible'){
            subMenu.style.visibility = 'hidden';
            document.removeEventListener('click', close);
        }else{
            subMenu.style.visibility = 'visible';
            document.addEventListener('click', close);
            isOpen = true;
        }

        return false;
    };

    var close = function(){

        if(!isOpen){
            var switchers = document.getElementsByClassName('js-wpml-ls-legacy-dropdown-click');

            for(var i=0;i<switchers.length;i++){
                var altLangs = switchers[i].getElementsByClassName('wpml-ls-sub-menu')[0];
                altLangs.style.visibility = 'hidden';
            }
        }

        isOpen = false;
    };

    return {
        'toggle': toggle
    };

})();