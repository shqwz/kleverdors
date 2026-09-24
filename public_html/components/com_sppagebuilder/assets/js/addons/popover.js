(() => {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const DELAY_TIME = 1000;

        function parseDataAttribute(dataAttribute) {
            // Split the string by ';' and create an array of key-value pairs
            const keyValuePairs = dataAttribute.split(';').map(pair => pair.trim());

            // Create an empty object to store the parsed values
            const parsedData = {};

            // Iterate over the key-value pairs and populate the parsedData object
            keyValuePairs.forEach(pair => {
                const [key, value] = pair.split(':').map(item => item.trim());
                if (key && value) {
                    parsedData[key] = value;
                }
            });
            
            return parsedData;
        }
        
        function getRightPosition({ markerRect, popoverRect, gap }) {
            const left = markerRect.left + markerRect.width + gap;
            const top = (markerRect.top + window.scrollY) + (markerRect.height / 2) - (popoverRect.height / 2);
            return { left, top };
        }
        function getLeftPosition({ markerRect, popoverRect, gap }) {
            const left = markerRect.left - popoverRect.width - gap;
            const top = (markerRect.top + window.scrollY) + (markerRect.height / 2) - (popoverRect.height / 2);
            return { left, top };
        }
        function getBottomPosition({ markerRect, popoverRect, gap }) {
            const left = markerRect.left + (markerRect.width / 2) - (popoverRect.width / 2);
            const top = (markerRect.top + window.scrollY) + markerRect.height + gap;
            return { left, top };
        }
        function getTopPosition({ markerRect, popoverRect, gap }) {
            const left = markerRect.left + (markerRect.width / 2) - (popoverRect.width / 2);
            const top = (markerRect.top + window.scrollY) - popoverRect.height - gap;
            return { left, top };
        }
        function adjustOverflowPosition({ left, top, popoverRect, gap }) {
            const viewPortHeight = window.innerHeight || document.documentElement.clientHeight;
            const viewPortWidth = window.innerWidth || document.documentElement.clientWidth;

            let adjustedTop = top;
            let adjustedLeft = left;

            const originalTop = top;
            const isVerticallyOutOfViewPort = originalTop - window.scrollY + popoverRect.height + gap > viewPortHeight;
            let isAdjusted = false;

            // Overflow top
            if (originalTop - window.scrollY < gap) {
                adjustedTop = gap + window.scrollY;
                isAdjusted = true;
                // Overflow bottom
            } else if (isVerticallyOutOfViewPort) {
                const overflowAmount = originalTop + popoverRect.height - viewPortHeight + gap;
                const finalCalculatedTop = Math.floor(originalTop - overflowAmount);
                adjustedTop = (finalCalculatedTop < gap ? gap + window.scrollY : finalCalculatedTop + window.scrollY)
                isAdjusted = true;
            }

            const originalLeft = left;
            const isHorizontallyOutOfViewPort = originalLeft + popoverRect.width + gap > viewPortWidth;

            // Overflow left
            if (originalLeft < gap) {
                adjustedLeft = gap;
                isAdjusted = true;
                // Overflow right
            } else if (isHorizontallyOutOfViewPort) {
                const overflowAmount = originalLeft + popoverRect.width - viewPortWidth + gap;
                const finalCalculatedLeft = Math.floor(originalLeft - overflowAmount);
                adjustedLeft = (finalCalculatedLeft < gap ? gap : finalCalculatedLeft);
                isAdjusted = true;
            }
            
            return { top: adjustedTop, left: adjustedLeft, isAdjusted };
        }
        
        function getPosition(markerNode, popoverNode) {
            const markerRect = markerNode.getBoundingClientRect();
            const popoverRect = popoverNode.getBoundingClientRect();

            const sppbData = popoverNode.getAttribute('sppb-data');
            const parsedData = parseDataAttribute(sppbData);
            const gap = !!parsedData.gap && !Number.isNaN(parsedData.gap) ? Number(parsedData.gap) : 10;
            
            if (parsedData.pos === 'right') {
                const rightPosition = getRightPosition({ markerRect, popoverRect, gap });
                const adjustedPosition = adjustOverflowPosition({ top: rightPosition.top, left: rightPosition.left, popoverRect, gap });
                return adjustedPosition
            } else if (parsedData.pos === 'left') {
                const rightPosition = getLeftPosition({ markerRect, popoverRect, gap });
                const adjustedPosition = adjustOverflowPosition({ top: rightPosition.top, left: rightPosition.left, popoverRect, gap });
                return adjustedPosition
            } else if (parsedData.pos === 'bottom') {
                const rightPosition = getBottomPosition({ markerRect, popoverRect, gap });
                const adjustedPosition = adjustOverflowPosition({ top: rightPosition.top, left: rightPosition.left, popoverRect, gap });
                return adjustedPosition
            } else if (parsedData.pos === 'top') {
                const rightPosition = getTopPosition({ markerRect, popoverRect, gap });
                const adjustedPosition = adjustOverflowPosition({ top: rightPosition.top, left: rightPosition.left, popoverRect, gap });
                return adjustedPosition
            } else {
                const markerTopRelativeToContainer = markerRect.top + window.scrollY;
                const markerLeftRelativeToContainer = markerRect.left;

                let adjustedPosition = { top, left, isAdjusted: false };

                // Check right
                const rightPosition = getRightPosition({ markerRect, popoverRect, gap });
                adjustedPosition = adjustOverflowPosition({ top: rightPosition.top, left: rightPosition.left, popoverRect, gap });
                if (!adjustedPosition.isAdjusted) {
                    return adjustedPosition;
                }

                // Check left
                const leftPosition = getLeftPosition({ markerRect, popoverRect, gap });
                adjustedPosition = adjustOverflowPosition({ top: leftPosition.top, left: leftPosition.left, popoverRect, gap });
                if (!adjustedPosition.isAdjusted) {
                    return adjustedPosition;
                }

                // Check bottom
                const bottomPosition = getBottomPosition({ markerRect, popoverRect, gap });
                adjustedPosition = adjustOverflowPosition({ top: bottomPosition.top, left: bottomPosition.left, popoverRect, gap });
                if (!adjustedPosition.isAdjusted) {
                    return adjustedPosition;
                }

                // Check top
                const topPosition = getTopPosition({ markerRect, popoverRect, gap });
                adjustedPosition = adjustOverflowPosition({ top: topPosition.top, left: topPosition.left, popoverRect, gap });
                if (!adjustedPosition.isAdjusted) {
                    return adjustedPosition;
                }

                return adjustOverflowPosition({
                    top: markerTopRelativeToContainer,
                    left: markerLeftRelativeToContainer,
                    popoverRect,
                    gap
                });
            }
        }

        const bodyListeners = [];

        function bodyClickHandler(popoverContent, markerNode) {
            return function () {
                closePopover(popoverContent, markerNode);
            }
        }

        function closePopover(popoverContent, markerNode) {
            if (markerNode) {
                markerNode.classList.remove('active');
            }
            popoverContent.classList.remove('sppb-open');
            document.body.removeEventListener('click', bodyClickHandler(popoverContent, markerNode));
        }

        function resetBodyListeners() {
            for (const listener of bodyListeners) {
                listener();
            }
            bodyListeners.length = 0;
        }

        const popoverAddonElements = document.querySelectorAll('.sppb-addon-popover')

        popoverAddonElements.forEach(popoverElement => {
            const markers = popoverElement.querySelectorAll('.sppb-popover-marker');
            const popoverContents = popoverElement.querySelectorAll('.sppb-popover-content');

            const wrapper = document.createElement('div');
            
            popoverContents.forEach((el) => {
                const popoverContentData = parseDataAttribute(el.getAttribute("sppb-data"));
                wrapper.setAttribute("id", "sppb-addon-" + popoverContentData?.id + "-portal");
                wrapper.setAttribute("class", "sppb-addon sppb-addon-popover");
                wrapper.style.position = "absolute";
                wrapper.style.left = "0px";
                wrapper.style.top = "0px";
                el.parentNode.insertBefore(wrapper, el);
                wrapper.appendChild(el);
              });

            for (let index = 0; index < markers.length; index++) {
                    document.body.append(wrapper);
                    const markerNode = markers[index];
                    const markerData = markerNode.getAttribute('sppb-data')
                    const parsedMarkerData = parseDataAttribute(markerData);
                
                    const containerNode = popoverElement.querySelector('#sppb-popover-inline');
                    if (!containerNode) {
                        return;
                    }

                    if (parsedMarkerData.mode === 'hover') {
                        let timeoutId;

                        function handleClose() {
                            timeoutId = setTimeout(() => {
                                popoverContents[index].classList.remove('sppb-open');
                                markerNode.classList.remove('active')
                                popoverContents[index].removeEventListener('mouseenter', clearTimeoutId)
                                popoverContents[index].removeEventListener('mouseleave', handleClose)
                                clearTimeout(timeoutId)
                            }, DELAY_TIME)
                        }

                        function clearTimeoutId() {
                            clearTimeout(timeoutId);
                        }

                        markerNode.addEventListener('mouseenter', hoverHandler(timeoutId));
                        markerNode.addEventListener('mouseleave', () => {
                            handleClose();

                            popoverContents[index].addEventListener('mouseenter', clearTimeoutId)
                            popoverContents[index].addEventListener('mouseleave', handleClose)
                        });
                    } else {
                        markerNode.addEventListener('click', clickHandler);
                    }

                    function hoverHandler(timeoutId) {
                        return function () {
                            clearTimeout(timeoutId)

                            const popoverNode = popoverContents[index];

                            if (!!popoverNode) {
                                popoverContents[index].classList.add('sppb-open');
                                markerNode.classList.add('active');
                                const {left, top } = getPosition(markerNode, popoverNode)
                                
                                popoverContents[index].style.left = `${left}px`;
                                popoverContents[index].style.top = `${top}px`;
                            }
                        }
                    }

                    function clickHandler(event) {
                        event.stopPropagation();

                        const popoverNode = popoverContents[index];
                        
                        if (!!markerNode && !!popoverNode) {
                            if (popoverContents[index].classList.contains('sppb-open')) {
                                closePopover(popoverContents[index], markerNode);
                            } else {
                                resetBodyListeners()
                                document.body.addEventListener('click', bodyClickHandler(popoverContents[index], markerNode));
                                bodyListeners.push(bodyClickHandler(popoverContents[index], markerNode))
                                
                                markerNode.classList.add('active');
                                popoverContents[index].classList.add('sppb-open');
                                const {left, top } = getPosition(markerNode, popoverNode)
                                
                                popoverContents[index].style.left = `${left}px`;
                                popoverContents[index].style.top = `${top}px`;
                            }
                        }
                    }
            }
        })
    });
})()
