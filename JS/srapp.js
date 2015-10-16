/** @namespace */
var srapp = (function (srapp, app, $)
{
    "use strict";
}());

// Modularized through an Immediately Invoked Function Expression
(function() {
    "use strict";

    function accordionify()
    {
        var segments = 0;
        var segmentId = 'accor';
        var accordion = {};
        accordion.toAccor = '';
        accordion.targets =
        {
            accorWrapper: '',
            accorTrigger: '',
            accorHeader: '',
            accorContent: '',
            accorButton: false
        };
        accordion.functions =
        {
            accorToggle: function(accor)
            {
                var wrapperId = $(accor).data('mywrapper');
                var accor = $(accor).attr('id');
                $(wrapperId + ' #' + accor + '.accorContent').toggleClass('accorOpened');
                $(wrapperId + ' #' + accor + '.accorButton').toggleClass('accorOpened');
            },
            init: function(el)
            {
                accordion.toAccor = el;
                accordion.targets =
                {
                    accorWrapper: accordion.toAccor.data('accorwrapper'),
                    accorTrigger: accordion.toAccor.data('accortrigger'),
                    accorHeader: accordion.toAccor.data('accorheader'),
                    accorContent: accordion.toAccor.data('accorcontent'),
                    accorButton: accordion.toAccor.data('accorbutton')
                };
                $(accordion.targets.accorWrapper).each(function()
                {
                    segments += 1;
                    segmentId = 'accor' + segments;
                    var currentId = '';
                    if (typeof $(this).attr('id') !== 'undefined')
                    {
                        currentId = $(this).attr('id');
                    }
                    if (currentId !== '')
                    {
                        $(this).attr('id', currentId + ' ' + segmentId);
                    }
                    else
                    {
                        $(this).attr('id', segmentId);
                    }
                    if(accordion.targets.accorButton)
                    {
                        $(this).prepend('<div class="accorButton accorTrigger" id="'+segmentId+'">+</div>')
                    }
                });

                $(accordion.targets.accorHeader).addClass('accordionHeader');
                $(accordion.targets.accorTrigger).addClass('accorTrigger');
                $(accordion.targets.accorContent).addClass('accorContent');
                $(accordion.targets.accorWrapper+' .accorTrigger').data('mywrapper', accordion.toAccor.data('accorwrapper'));
                for (var i = 1; i <= segments; i++)
                {
                    var segmentId = 'accor' + i;
                    var targetSegment = '#accor' + i + accordion.targets.accorWrapper + ' ';
                    var currentId = '';
                    if (typeof $(targetSegment+accordion.targets.accorHeader).attr('id') !== 'undefined')
                    {
                        currentId = $(targetSegment+accordion.targets.accorHeader).attr('id');
                        if (currentId !== '' && currentId !== segmentId)
                        {
                            $(targetSegment+accordion.targets.accorHeader).attr('id', currentId + ' ' + segmentId);
                        }
                        else
                        {
                            $(targetSegment+accordion.targets.accorHeader).attr('id', segmentId);
                        }
                        currentId = '';
                    }
                    else
                    {
                        $(targetSegment+accordion.targets.accorHeader).attr('id', segmentId);
                    }
                    if (typeof $(targetSegment+accordion.targets.accorTrigger).attr('id') !== 'undefined')
                    {
                        currentId = $(targetSegment+accordion.targets.accorTrigger).attr('id');
                        if (currentId !== '' && currentId !== segmentId)
                        {
                            $(targetSegment+accordion.targets.accorTrigger).attr('id', currentId + ' ' + segmentId);
                        }
                        else
                        {
                            $(targetSegment+accordion.targets.accorTrigger).attr('id', segmentId);
                        }
                        currentId = '';
                    }
                    else
                    {
                        $(targetSegment+accordion.targets.accorTrigger).attr('id', segmentId);
                    }
                    if (typeof $(targetSegment+accordion.targets.accorContent).attr('id') !== 'undefined')
                    {
                        currentId = $(targetSegment+accordion.targets.accorContent).attr('id');
                        if (currentId !== '' && currentId !== segmentId)
                        {
                            $(targetSegment+accordion.targets.accorContent).attr('id', currentId + ' ' + segmentId);
                        }
                        else
                        {
                            $(targetSegment+accordion.targets.accorContent).attr('id', segmentId);
                        }
                        currentId = '';
                    }
                    else
                    {
                        $(targetSegment+accordion.targets.accorContent).attr('id', segmentId);
                    }
                    segmentId = '';
                }
                segments = 0;
            }
        };

        $('.accordionify').each(function()
        {
            var toAccordion = $(this);
            accordion.functions.init(toAccordion);
        });
        $('.accorTrigger').click(function(event)
        {
            var accorClicked = $(this);
            accordion.functions.accorToggle(accorClicked);
        });
    }

    //Images
    /**
     * Looking for popImage class on outer wrapper
     */
    function popImage()
    {
        var imgCount = 0;
        var popImages = {};
        popImages.imgSet = {};
        popImages.viewer = '';
        popImages.functions =
        {
            init: function(el)
            {
                popImages.viewer = el.data('popviewer');
                popImages.imgSet.imgId = el.data('poptargets');
                $('.popImage' + ' ' + el.data('poptargets')).each(function()
                {
                    imgCount += 1;
                    $(this).addClass('imageInWaiting');
                    var currentId = '';
                    var imgId =  'popImage' + imgCount;
                    if (typeof $(this).attr('id') !== 'undefined')
                    {
                        currentId = $(this).attr('id');
                    }
                    if (currentId !== '')
                    {
                        $(this).attr('id', currentId + ' ' + imgId);
                    }
                });
                $(popImages.viewer).addClass('popImageViewer');
                $(popImages.viewer).append('<a href="" id="viewerLink"><img src="" id="viewerImage"/></a>');
            },
            populateViewer: function(cid)
            {
                var imgSrc = cid.attr('src');
                var imgPid = cid.data('pid');
                $('#viewerImage').attr('src', imgSrc);
                $('#viewerImage').data('pid', imgPid);
            }
        };
        $('.popImage').each(function()
        {
            var toPop = $(this);
            popImages.functions.init(toPop);
        });
        $(popImages.imgSet.imgId).click(function()
        {
            var clicked = $(this);
            popImages.functions.populateViewer(clicked);
        });
    }

    function animateify()
    {
        var animated = {};
        animated.targets = {};
        animated.functions =
        {
            init: function(el)
            {
                animated.map = el;
                animated.targets.setClass = animated.map.data('animateifysetcontainer');
                animated.targets.frameClass = animated.map.data('animateifyframeclass');
                animated.targets.firstFrame = animated.map.data('animateifyfirstframe');
                animated.targets.lastFrame = animated.map.data('animateifylastframe');
                animated.targets.playToggle = animated.map.data('animateifyplaytoggle');
                animated.targets.frameCount = 0;
                $(animated.targets.frameClass).each(function()
                {
                    animated.targets.frameCount += 1;
                    var frameId = 'frameId' + animated.targets.frameCount;
                    $(this).addClass('animateifyFrame');
                    $(this).attr('id', frameId);
                    $(this).data('mywrapper', animated.targets.container);
                });
                $(animated.targets.firstFrame).addClass('animateifyCurrentFrame');
                for (var i = animated.targets.frameCount; i == 1; i--)
                {
                    var frameId = 'frameId' + (frameCount - (i - 1));
                    $(animated.targets.container + ' #' + frameId).css('z-index', i);
                }
                $(animated.targets.playToggle).data('mywrapper', animated.targets.setClass);
                $(animated.targets.playToggle).data('framecount', animated.targets.frameCount);
            },
            play: function(target)
            {
                var thisFrameCount = target.data('framecount');
                var animationContainer = target.data('mywrapper');
                var firstFrame = $(animationContainer + ' .animateifyCurrentFrame');
                target.fadeToggle();
                if ($(animationContainer + ' ' + animated.targets.lastFrame)[0] == firstFrame[0])
                {
                    function backward()
                    {
                        var animation = setInterval(function()
                        {
                            var firstFrame = $(animationContainer + ' .animateifyCurrentFrame');
                            firstFrame
                            .toggleClass('animateifyCurrentFrame')
                            .prev()
                            .toggleClass('animateifyCurrentFrame');
                            if ($(animationContainer).children(animated.targets.firstFrame).hasClass('animateifyCurrentFrame'))
                            {
                                clearInterval(animation);
                            }
                        },
                        300);
                    }
                    backward();
                }
                else
                {
                    function forward()
                    {
                        var animation = setInterval(function()
                        {
                            var firstFrame = $(animationContainer + ' .animateifyCurrentFrame');
                            firstFrame
                            .delay(500)
                            .toggleClass('animateifyCurrentFrame')
                            .next()
                            .toggleClass('animateifyCurrentFrame');
                            if ($(animationContainer).children(animated.targets.lastFrame).hasClass('animateifyCurrentFrame'))
                            {
                                clearInterval(animation);
                            }
                        },
                        300);
                    }
                    forward();
                }
                target.fadeToggle();
            }
        };
        $('.animateify').each(function()
        {
            var toAnimate = $(this);
            animated.functions.init(toAnimate);
            window.onload(function()
            {
                $(animated.targets.playToggle).fadeIn();
            });
        });
        $(animated.targets.playToggle).click(function(e)
        {
            var toPlay = $(this);
            animated.functions.play(toPlay);
        });
    }

    $(document).ready(function()
    {
        accordionify();
        popImage();
        animateify();
    });
})();
