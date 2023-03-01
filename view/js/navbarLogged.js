
var seachFormIsRunning = 0;
$(document).ready(function () {
    setTimeout(function () {
        $('.nav li.navsub-toggle a:not(.selected) + ul').hide();
        var navsub_toggle_selected = $('.nav li.navsub-toggle a.selected');
        navsub_toggle_selected.next().show();
        navsub_toggle_selected = navsub_toggle_selected.parent();

        var navsub_toggle_selected_stop = 24;
        while (navsub_toggle_selected.length) {
            if ($.inArray(navsub_toggle_selected.prop('localName'), ['li', 'ul']) == -1)
                break;
            if (navsub_toggle_selected.prop('localName') == 'ul') {
                navsub_toggle_selected.show().prev().addClass('selected');
            }
            navsub_toggle_selected = navsub_toggle_selected.parent();

            navsub_toggle_selected_stop--;
            if (navsub_toggle_selected_stop < 0)
                break;
        }
    }, 500);


    $('.nav').on('click', 'li.navsub-toggle a:not(.selected)', function (e) {
        var a = $(this),
                b = a.next();
        if (b.length) {
            e.preventDefault();

            a.addClass('selected');
            b.slideDown();

            var c = a.closest('.nav').find('li.navsub-toggle a.selected').not(a).removeClass('selected').next();

            if (c.length)
                c.slideUp();
        }
    });

    $('#searchForm').submit(function (event) {
        if (seachFormIsRunning) {
            event.preventDefault();
            return false;
        }
        seachFormIsRunning = 1;
        var str = $('#searchFormInput').val();
        if (isMediaSiteURL(str)) {
            event.preventDefault();
            console.log("searchForm is URL " + str);
            seachFormPlayURL(str);
            return false;
        } else {
            console.log("searchForm submit " + str);
            document.location = webSiteRootURL + "?search=" + str;
        }
    });

    $('#buttonMenu').on("click.sidebar", function (event) {
        event.stopPropagation();
        YPTSidebarToggle();
    });
    $("#sidebar").on("click", function (event) {
        event.stopPropagation();
    });
    $("#buttonSearch").click(function (event) {
        event.stopPropagation();
        if (isSearchOpen()) {
            closeSearchMenu();
        } else {
            openSearchMenu();
        }
    });
    $("#buttonMyNavbar").click(function (event) {
        event.stopPropagation();
        if (isMyNMavbarOpen()) {
            closeRightMenu();
        } else {
            openRightMenu();
        }
    });
    var wasMobile = true;
    $(window).resize(function () {
        if ($(window).width() > 767) {
            // Window is bigger than 767 pixels wide - show search again, if autohide by mobile.
            if (wasMobile) {
                wasMobile = false;
            }
        }
        if ($(window).width() < 767) {
            // Window is smaller 767 pixels wide - show search again, if autohide by mobile.
            if (wasMobile == false) {
                wasMobile = true;
            }
        }
    });

    $(window).resize(function () {
        if (!isScreeWidthCollapseSize()) {
            $("#myNavbar").css({display:''});
            $("#myNavbar").removeClass('animate__bounceOutRight');
            var selector = '#buttonMyNavbar svg';
            $(selector).removeClass('active');
            $(selector).attr('aria-expanded', 'false');
            
            $("#mysearch").css({display:''});
            $("#mysearch").removeClass('animate__bounceOutUp');
        }
    });
});

function isScreeWidthCollapseSize() {
    return $('body').width() <= 767;
}

async function closeLeftMenu() {
    var selector = '#buttonMenu svg';
    $(selector).removeClass('active');
    YPTSidebarClose();
}
async function openLeftMenu() {
    if (isScreeWidthCollapseSize()) {
        closeRightMenu();
        closeSearchMenu();
    }
    var selector = '#buttonMenu svg';
    $(selector).addClass('active');
    YPTSidebarOpen();
}

async function closeRightMenu() {
    var selector = '#buttonMyNavbar svg';
    $(selector).removeClass('active');
    $("#myNavbar").removeClass('animate__bounceInRight');
    $("#myNavbar").addClass('animate__bounceOutRight');
    setTimeout(function () {
        $("#myNavbar").hide();
    }, 500);
}
async function openRightMenu() {
    if (isScreeWidthCollapseSize()) {
        closeLeftMenu();
        closeSearchMenu();
    }
    var selector = '#buttonMyNavbar svg';
    $(selector).addClass('active');
    $("#myNavbar").removeClass('animate__bounceOutRight');
    $("#myNavbar").show();
    $("#myNavbar").addClass('animate__animated animate__bounceInRight');
}

async function closeSearchMenu() {
    $("#mysearch").removeClass('animate__bounceInDown');
    $("#mysearch").addClass('animate__bounceOutUp');
    setTimeout(function () {
        $("#mysearch").hide();
    }, 500);
}
async function openSearchMenu() {
    if (isScreeWidthCollapseSize()) {
        closeLeftMenu();
        closeRightMenu();
    }
    $("#mysearch").removeClass('animate__bounceOutUp');
    $("#mysearch").show();
    $("#mysearch").addClass('animate__animated animate__bounceInDown');
}

async function seachFormPlayURL(url) {
    modal.showPleaseWait();
    $.ajax({
        url: webSiteRootURL + 'view/url2Embed.json.php',
        method: 'POST',
        data: {
            'url': url
        },
        success: function (response) {
            seachFormIsRunning = 0;
            if (response.error) {
                modal.hidePleaseWait();
                avideoToast(response.msg);
            } else {
                if (typeof linksToEmbed === 'function') {
                    document.location = response.playEmbedLink;
                } else
                if (typeof flixFullScreen == 'function') {
                    flixFullScreen(response.playEmbedLink, response.playLink);
                    modal.hidePleaseWait();
                } else {
                    document.location = response.playLink;
                }
            }
        }
    });
}

function isSearchOpen() {
    return $('#mysearch').hasClass('animate__bounceInDown');
}
function isMyNMavbarOpen() {
    return $('#myNavbar').hasClass('animate__bounceInRight');
}
async function YPTSidebarToggle() {
    if (YPTSidebarIsOpen()) {
        closeLeftMenu()
    } else {
        openLeftMenu();
    }
}
function YPTSidebarIsOpen() {
    return $('body').hasClass('youtube');
}
async function YPTSidebarOpen() {
    $("#sidebar").removeClass('animate__bounceOutLeft');
    $("#sidebar").show();
    $("#sidebar").addClass('animate__animated animate__bounceInLeft');
    setTimeout(function () {
        $('body').addClass('youtube');
    }, 500);
    youTubeMenuIsOpened = true;
}
async function YPTSidebarClose() {
    $("#sidebar").removeClass('animate__bounceInLeft');
    $("#sidebar").addClass('animate__bounceOutLeft');
    setTimeout(function () {
        $('body').removeClass('youtube');
        $("#sidebar").hide();
    }, 500);
    youTubeMenuIsOpened = false;
}

async function YPTHidenavbar() {
    if (typeof inIframe == 'undefined') {
        setTimeout(function () {
            YPTHidenavbar()
        }, 500);
    } else {
        if (inIframe()) {
            $("#mainNavBar").hide();
            $("body").css("padding-top", "0");
        }
    }
}
