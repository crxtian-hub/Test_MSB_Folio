let publicPageController;
let passwordInputsController;
let barbaStarted = false;
let pendingProjectCoverTransition = null;
let pendingHomeContainerUnpin = null;
const HOME_TO_PROJECT_TRANSITION_CLASS = 'is-home-project-cover-transition';

function isAuthenticatedSession() {
    return document.body?.dataset?.authenticated === '1';
}

function nextFrame() {
    return new Promise((resolve) => window.requestAnimationFrame(resolve));
}

function getProjectKeyFromElement(element) {
    return element?.dataset?.projectKey || null;
}

function escapeAttributeValue(value) {
    if (!value) {
        return '';
    }

    if (window.CSS?.escape) {
        return window.CSS.escape(value);
    }

    return value.replace(/["\\]/g, '\\$&');
}

function getValidRect(element) {
    if (!element) {
        return null;
    }

    const rect = element.getBoundingClientRect();

    if (rect.width === 0 || rect.height === 0) {
        return null;
    }

    return rect;
}

function pinContainerToViewport(container) {
    if (!container) {
        return () => {};
    }

    const previousStyles = {
        position: container.style.position,
        inset: container.style.inset,
        top: container.style.top,
        left: container.style.left,
        width: container.style.width,
        minHeight: container.style.minHeight,
        zIndex: container.style.zIndex,
        visibility: container.style.visibility,
        pointerEvents: container.style.pointerEvents,
    };

    container.style.position = 'fixed';
    container.style.inset = '0';
    container.style.top = '0';
    container.style.left = '0';
    container.style.width = '100%';
    container.style.minHeight = '100vh';
    container.style.zIndex = '0';
    container.style.visibility = 'hidden';
    container.style.pointerEvents = 'none';

    return () => {
        container.style.position = previousStyles.position;
        container.style.inset = previousStyles.inset;
        container.style.top = previousStyles.top;
        container.style.left = previousStyles.left;
        container.style.width = previousStyles.width;
        container.style.minHeight = previousStyles.minHeight;
        container.style.zIndex = previousStyles.zIndex;
        container.style.visibility = previousStyles.visibility;
        container.style.pointerEvents = previousStyles.pointerEvents;
    };
}

function queueHomeContainerUnpin(unpin) {
    pendingHomeContainerUnpin = typeof unpin === 'function' ? unpin : null;
}

function flushHomeContainerUnpin() {
    if (typeof pendingHomeContainerUnpin === 'function') {
        pendingHomeContainerUnpin();
    }

    pendingHomeContainerUnpin = null;
}

function createScopedController(previousController) {
    previousController?.abort();
    return new AbortController();
}

function getHomeGridEntranceCards(grid, excludedCards = []) {
    if (!grid) {
        return [];
    }

    const excluded = new Set(excludedCards.filter(Boolean));
    return Array.from(grid.querySelectorAll('.projectCard')).filter((card) => !excluded.has(card));
}

function primeHomeGridEntrance(grid, excludedCards = []) {
    if (!grid) {
        return;
    }

    grid.style.opacity = '0';
    grid.style.willChange = 'opacity';

    const cards = getHomeGridEntranceCards(grid, excludedCards);

    cards.forEach((card) => {
        card.style.opacity = '0';
        card.style.visibility = 'hidden';
        card.style.willChange = 'opacity';
    });
}

function primeHomeGridReturnCards(grid, selectedCard = null) {
    if (!grid) {
        return;
    }

    grid.style.opacity = '0';
    grid.style.willChange = 'opacity';

    const cards = getHomeGridEntranceCards(grid, [selectedCard]);

    cards.forEach((card) => {
        card.style.visibility = 'visible';
        card.style.opacity = '0';
        card.style.willChange = 'opacity';
    });
}

function resetHomeGridCardStyles(grid) {
    if (!grid) {
        return;
    }

    grid.style.removeProperty('opacity');
    grid.style.removeProperty('willChange');

    const cards = Array.from(grid.querySelectorAll('.projectCard'));

    cards.forEach((card) => {
        card.style.removeProperty('opacity');
        card.style.removeProperty('visibility');
        card.style.removeProperty('willChange');
    });
}

async function revealHomeGridEntrance(grid, excludedCards = []) {
    if (!grid) {
        return Promise.resolve();
    }

    const cards = getHomeGridEntranceCards(grid, excludedCards);

    cards.forEach((card) => {
        card.style.visibility = 'visible';
    });

    grid.getBoundingClientRect();

    if (!window.gsap) {
        grid.style.opacity = '1';
        grid.style.removeProperty('willChange');
        cards.forEach((card) => {
            card.style.opacity = '1';
            card.style.removeProperty('willChange');
        });
        return Promise.resolve();
    }

    window.gsap.killTweensOf(grid);
    window.gsap.set(grid, {
        opacity: 0,
    });

    await nextFrame();

    const gridFade = window.gsap.to(grid, {
        opacity: 1,
        duration: 0.45,
        ease: 'power2.out',
        overwrite: 'auto',
        onComplete: () => {
            grid.style.removeProperty('willChange');
        }
    });

    if (cards.length === 0) {
        return gridFade;
    }

    const cardsFade = window.gsap.fromTo(cards, {
        opacity: 0,
    }, {
        opacity: 1,
        duration: 0.35,
        stagger: 0.03,
        ease: 'power2.out',
        overwrite: 'auto',
        onComplete: () => {
            cards.forEach((card) => {
                card.style.removeProperty('willChange');
            });
        }
    });

    return Promise.all([gridFade, cardsFade]);
}

async function revealHomeGridReturnCards(grid, selectedCard = null) {
    if (!grid) {
        return Promise.resolve();
    }

    ensureProjectCardVisible(selectedCard, {
        preserveCoverVisibility: true,
    });

    const cards = getHomeGridEntranceCards(grid, [selectedCard]);

    cards.forEach((card) => {
        card.style.visibility = 'visible';
        card.style.opacity = '0';
        card.style.willChange = 'opacity';
    });

    grid.getBoundingClientRect();

    if (!window.gsap) {
        grid.style.opacity = '1';
        grid.style.removeProperty('willChange');
        cards.forEach((card) => {
            card.style.opacity = '1';
            card.style.removeProperty('willChange');
        });
        return Promise.resolve();
    }

    window.gsap.killTweensOf(grid);
    window.gsap.set(grid, {
        opacity: 0,
    });

    if (cards.length > 0) {
        window.gsap.killTweensOf(cards);
        window.gsap.set(cards, {
            opacity: 0,
            visibility: 'visible',
        });
    }

    await nextFrame();

    const gridFade = window.gsap.to(grid, {
        opacity: 1,
        duration: 0.45,
        ease: 'power2.out',
        overwrite: 'auto',
        onComplete: () => {
            grid.style.removeProperty('willChange');
        }
    });

    if (cards.length === 0) {
        return gridFade;
    }

    const cardsFade = window.gsap.to(cards, {
        opacity: 1,
        duration: 0.55,
        ease: 'none',
        overwrite: 'auto',
        onComplete: () => {
            cards.forEach((card) => {
                card.style.removeProperty('willChange');
            });
        }
    });

    return Promise.all([gridFade, cardsFade]);
}

async function runHomeGridEntrance(container) {
    const grid = container?.querySelector('.projectsGrid');

    if (!grid || grid.dataset.homeGridAnimated === 'true') {
        return;
    }

    primeHomeGridEntrance(grid);
    await nextFrame();
    await revealHomeGridEntrance(grid);
    grid.dataset.homeGridAnimated = 'true';
}

function initGridToggle(signal, options = {}) {
    const { animate = true } = options;
    const gridButton = document.querySelector('.gridButton');
    const gridCols = document.querySelectorAll('.gridCol');

    if (!gridButton || gridCols.length === 0) {
        return;
    }

    let isGridOpen = true;

    if (!animate || !window.gsap) {
        const applyGridState = (open) => {
            gridCols.forEach((column) => {
                column.style.transform = open ? 'translateX(0%)' : 'translateX(100%)';
                column.style.opacity = open ? '1' : '0';
            });
        };

        gridButton.addEventListener('click', () => {
            isGridOpen = !isGridOpen;
            applyGridState(isGridOpen);
        }, { signal });

        return;
    }

    gridButton.addEventListener('click', () => {
        window.gsap.to('.gridCol', {
            x: isGridOpen ? '100%' : '0%',
            opacity: isGridOpen ? 0 : 1,
            duration: 0.5,
            stagger: 0.05,
            ease: isGridOpen ? 'power2.in' : 'power2.out'
        });

        window.gsap.to(gridButton, {
            duration: 0.3
        });

        isGridOpen = !isGridOpen;
    }, { signal });
}

function initPasswordInputs() {
    passwordInputsController = createScopedController(passwordInputsController);

    const { signal } = passwordInputsController;
    const passwordInputs = document.querySelectorAll('.pwLoginContainer input[type="password"]');

    if (passwordInputs.length === 0) {
        return;
    }

    const measureSpan = document.createElement('span');
    measureSpan.style.position = 'absolute';
    measureSpan.style.visibility = 'hidden';
    measureSpan.style.whiteSpace = 'pre';
    measureSpan.style.top = '-9999px';
    document.body.appendChild(measureSpan);

    signal.addEventListener('abort', () => {
        measureSpan.remove();
    }, { once: true });

    const maskChar = '\u2022';

    const updatePasswordWidth = (input) => {
        const styles = window.getComputedStyle(input);
        measureSpan.style.fontFamily = styles.fontFamily;
        measureSpan.style.fontSize = styles.fontSize;
        measureSpan.style.fontWeight = styles.fontWeight;
        measureSpan.style.letterSpacing = styles.letterSpacing;

        const paddingLeft = parseFloat(styles.paddingLeft) || 0;
        const paddingRight = parseFloat(styles.paddingRight) || 0;
        const valueLength = input.value.length;

        if (!valueLength) {
            input.style.width = '10vw';
            return;
        }

        measureSpan.textContent = maskChar.repeat(valueLength);
        const textWidth = measureSpan.getBoundingClientRect().width;
        const totalWidth = Math.max(textWidth + paddingLeft + paddingRight, 0);
        input.style.width = `${totalWidth}px`;
    };

    passwordInputs.forEach((input) => {
        updatePasswordWidth(input);
        input.addEventListener('input', () => updatePasswordWidth(input), { signal });
        input.addEventListener('blur', () => updatePasswordWidth(input), { signal });
    });
}

function layoutHomeMasonry(grid) {
    const getColumnCount = () => {
        return window.matchMedia('(max-width: 800px)').matches ? 2 : 4;
    };

    const items = Array.from(grid.querySelectorAll('.projectCard'));

    if (items.length === 0) {
        grid.style.height = '';
        return;
    }

    const columns = getColumnCount();
    const styles = window.getComputedStyle(grid);
    const columnGap = parseFloat(styles.columnGap) || 0;
    const rowGap = parseFloat(styles.rowGap);
    const isMobile = window.matchMedia('(max-width: 800px)').matches;
    const gap = isMobile
        ? columnGap
        : (Number.isFinite(rowGap) ? rowGap : columnGap);
    const gridWidth = grid.clientWidth;
    const itemWidth = (gridWidth - columnGap * (columns - 1)) / columns;
    const heights = Array(columns).fill(0);

    grid.classList.add('is-enhanced');

    items.forEach((item, index) => {
        item.style.width = `${itemWidth}px`;

        const itemHeight = item.getBoundingClientRect().height;
        const columnIndex = index % columns;
        const left = (itemWidth + columnGap) * columnIndex;
        const top = heights[columnIndex];

        item.style.transform = `translate(${left}px, ${top}px)`;
        heights[columnIndex] = top + itemHeight + gap;
    });

    grid.style.height = `${Math.max(...heights) - gap}px`;

}

async function prepareHomeGrid(grid) {
    if (!grid) {
        return;
    }

    const images = Array.from(grid.querySelectorAll('img'));

    await Promise.all(images.map(async (img) => {
        if (typeof img.decode === 'function') {
            try {
                await img.decode();
                return;
            } catch {
                return;
            }
        }

        if (img.complete) {
            return;
        }

        await new Promise((resolve) => {
            img.addEventListener('load', resolve, { once: true });
            img.addEventListener('error', resolve, { once: true });
        });
    }));

    layoutHomeMasonry(grid);
    await new Promise((resolve) => window.requestAnimationFrame(resolve));
    layoutHomeMasonry(grid);
}

function initHomeMasonry(signal, options = {}) {
    const { immediate = false } = options;
    const grid = document.querySelector('.projectsGrid');

    if (!grid) {
        return;
    }

    const scheduleLayout = () => {
        if (immediate) {
            layoutHomeMasonry(grid);
            return;
        }

        window.requestAnimationFrame(() => layoutHomeMasonry(grid));
    };

    window.addEventListener('load', scheduleLayout, { signal });
    window.addEventListener('resize', scheduleLayout, { signal });

    grid.querySelectorAll('img').forEach((img) => {
        if (img.complete) {
            return;
        }

        img.addEventListener('load', scheduleLayout, { signal });
    });

    scheduleLayout();
}

function getGridProjectOrder(grid) {
    if (!grid) {
        return [];
    }

    return Array.from(grid.querySelectorAll('.projectCard[data-project-id]'))
        .map((card) => Number.parseInt(card.dataset.projectId, 10))
        .filter((projectId) => Number.isInteger(projectId));
}

function swapProjectCards(sourceCard, targetCard) {
    if (!sourceCard || !targetCard || sourceCard === targetCard) {
        return;
    }

    const parent = sourceCard.parentNode;

    if (!parent || parent !== targetCard.parentNode) {
        return;
    }

    const sourceSibling = sourceCard.nextSibling;
    const targetSibling = targetCard.nextSibling;

    if (sourceSibling === targetCard) {
        parent.insertBefore(targetCard, sourceCard);
        return;
    }

    if (targetSibling === sourceCard) {
        parent.insertBefore(sourceCard, targetCard);
        return;
    }

    parent.insertBefore(sourceCard, targetSibling);
    parent.insertBefore(targetCard, sourceSibling);
}

async function persistProjectOrder(endpoint, orderedIds, csrfToken) {
    const response = await window.fetch(endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({
            ordered_ids: orderedIds,
        }),
    });

    if (!response.ok) {
        throw new Error(`Unable to persist project order: ${response.status}`);
    }
}

function initProjectSorting(signal) {
    if (!isAuthenticatedSession()) {
        return;
    }

    const grid = document.querySelector('.projectsGrid[data-sort-enabled="true"]');
    const endpoint = grid?.dataset?.sortEndpoint;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    if (!grid || !endpoint || !window.fetch) {
        return;
    }

    let draggingCard = null;
    let armedDragCard = null;
    let lastSwapTarget = null;
    let layoutFrame = null;
    let saveQueue = Promise.resolve();
    let lastPersistedSignature = getGridProjectOrder(grid).join(',');
    let dragStartSignature = lastPersistedSignature;

    const setCardsDraggable = (isDraggable) => {
        grid.querySelectorAll('.projectCard[data-project-id]').forEach((card) => {
            card.setAttribute('draggable', isDraggable ? 'true' : 'false');
        });
    };

    const disarmDragCard = () => {
        if (armedDragCard && !draggingCard) {
            armedDragCard.setAttribute('draggable', 'false');
        }

        armedDragCard = null;
    };

    const scheduleLayout = () => {
        if (layoutFrame !== null) {
            return;
        }

        layoutFrame = window.requestAnimationFrame(() => {
            layoutFrame = null;
            layoutHomeMasonry(grid);
        });
    };

    const queuePersist = (orderedIds) => {
        const signature = orderedIds.join(',');

        if (signature === lastPersistedSignature) {
            return;
        }

        saveQueue = saveQueue.finally(async () => {
            if (signature === lastPersistedSignature) {
                return;
            }

            grid.classList.add('is-order-saving');

            try {
                await persistProjectOrder(endpoint, orderedIds, csrfToken);
                lastPersistedSignature = signature;
            } catch (error) {
                console.error(error);
                window.alert('Impossibile salvare il nuovo ordine della griglia. Riprova.');
            } finally {
                grid.classList.remove('is-order-saving');
            }
        });
    };

    setCardsDraggable(false);

    grid.addEventListener('pointerdown', (event) => {
        const handle = event.target.closest('.projectCardSortHandle');

        if (!handle) {
            return;
        }

        const card = handle.closest('.projectCard[data-project-id]');

        if (!card) {
            return;
        }

        disarmDragCard();
        card.setAttribute('draggable', 'true');
        armedDragCard = card;
    }, { signal });

    window.addEventListener('pointerup', disarmDragCard, { signal });
    window.addEventListener('pointercancel', disarmDragCard, { signal });

    grid.addEventListener('dragstart', (event) => {
        const card = event.target.closest('.projectCard[data-project-id]');

        if (!card || card !== armedDragCard) {
            event.preventDefault();
            return;
        }

        dragStartSignature = getGridProjectOrder(grid).join(',');
        draggingCard = card;
        lastSwapTarget = null;
        armedDragCard = null;
        grid.classList.add('is-sorting');
        card.classList.add('is-dragging');

        if (event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', card.dataset.projectId ?? '');
        }
    }, { signal });

    grid.addEventListener('dragover', (event) => {
        if (!draggingCard) {
            return;
        }

        event.preventDefault();

        if (event.dataTransfer) {
            event.dataTransfer.dropEffect = 'move';
        }
    }, { signal });

    grid.addEventListener('dragenter', (event) => {
        if (!draggingCard) {
            return;
        }

        const targetCard = event.target.closest('.projectCard[data-project-id]');

        if (!targetCard || targetCard === draggingCard || targetCard === lastSwapTarget) {
            return;
        }

        lastSwapTarget = targetCard;
        swapProjectCards(draggingCard, targetCard);
        scheduleLayout();
    }, { signal });

    grid.addEventListener('dragend', () => {
        if (!draggingCard) {
            setCardsDraggable(false);
            return;
        }

        const draggedCard = draggingCard;
        draggingCard = null;
        lastSwapTarget = null;
        grid.classList.remove('is-sorting');
        draggedCard.classList.remove('is-dragging');
        draggedCard.setAttribute('draggable', 'false');
        layoutHomeMasonry(grid);

        const currentOrder = getGridProjectOrder(grid);
        const currentSignature = currentOrder.join(',');

        if (currentSignature !== dragStartSignature) {
            queuePersist(currentOrder);
        }
    }, { signal });

    signal.addEventListener('abort', () => {
        if (layoutFrame !== null) {
            window.cancelAnimationFrame(layoutFrame);
            layoutFrame = null;
        }

        setCardsDraggable(false);
        grid.classList.remove('is-sorting');
    }, { once: true });
}

function initPublicPage(options = {}) {
    const { animate = true } = options;
    publicPageController = createScopedController(publicPageController);
    const { signal } = publicPageController;

    initGridToggle(signal, {
        animate,
    });
    initHomeMasonry(signal, options);
    initProjectSorting(signal);
}

function updateTitleFromHtml(html) {
    if (!html) {
        return;
    }

    const nextDocument = new DOMParser().parseFromString(html, 'text/html');
    const nextTitle = nextDocument.querySelector('title')?.textContent;

    if (nextTitle) {
        document.title = nextTitle;
    }
}

function updatePersistentNavFromHtml(html) {
    if (!html) {
        return;
    }

    const nextDocument = new DOMParser().parseFromString(html, 'text/html');
    const nextNav = nextDocument.querySelector('[data-persistent-nav]');
    const currentNav = document.querySelector('[data-persistent-nav]');

    if (!nextNav || !currentNav) {
        return;
    }

    currentNav.replaceWith(nextNav);
}

async function animateInfoNavbarExit() {
    const currentNav = document.querySelector('.navbarGuest.navbarGuestInfo[data-persistent-nav]');

    if (!currentNav) {
        return;
    }

    const subtitle = currentNav.querySelector('.hMRSFSubtitle');
    const infoActive = currentNav.querySelector('.infoAActive');

    if (!window.gsap) {
        if (subtitle) {
            subtitle.style.opacity = '0';
        }

        if (infoActive) {
            infoActive.style.backgroundColor = 'transparent';
            infoActive.style.color = '#000';
        }

        return;
    }

    const tweens = [];

    if (subtitle) {
        tweens.push(window.gsap.to(subtitle, {
            opacity: 0,
            duration: 0.2,
            ease: 'power1.out',
            overwrite: 'auto',
        }));
    }

    if (infoActive) {
        tweens.push(window.gsap.to(infoActive, {
            backgroundColor: 'transparent',
            color: '#000',
            duration: 0.2,
            ease: 'power1.out',
            overwrite: 'auto',
        }));
    }

    if (tweens.length === 0) {
        return;
    }

    await Promise.all(tweens);
}

function setHomeToProjectCoverTransitionState(isActive) {
    document.documentElement.classList.toggle(HOME_TO_PROJECT_TRANSITION_CLASS, Boolean(isActive));
}

function getProjectCoverTransitionCloneZIndex() {
    const guestNavbar = document.querySelector('.navbarGuest');

    if (!guestNavbar) {
        return 999;
    }

    const guestNavbarZIndex = Number.parseInt(window.getComputedStyle(guestNavbar).zIndex, 10);

    if (Number.isNaN(guestNavbarZIndex)) {
        return 999;
    }

    return Math.max(guestNavbarZIndex - 1, 0);
}

function cleanupPendingProjectCoverTransition() {
    setHomeToProjectCoverTransitionState(false);

    if (!pendingProjectCoverTransition) {
        return;
    }

    pendingProjectCoverTransition.clone?.remove();
    pendingProjectCoverTransition.sourceImage?.style.removeProperty('visibility');
    pendingProjectCoverTransition = null;
}

function revealPendingProjectCoverTransitionTarget() {
    setHomeToProjectCoverTransitionState(false);
    pendingProjectCoverTransition?.targetImage?.style.removeProperty('visibility');
}

function captureProjectCoverTransition(source) {
    const image = source?.image;
    const href = source?.href;
    const projectKey = source?.projectKey ?? getProjectKeyFromElement(image);

    cleanupPendingProjectCoverTransition();

    const shouldHideIncomingProjectCover = image?.classList?.contains('projectCover')
        && Boolean(href);
    setHomeToProjectCoverTransitionState(shouldHideIncomingProjectCover);

    if (!image || !href) {
        return;
    }

    const rect = image.getBoundingClientRect();
    const computedStyle = window.getComputedStyle(image);
    const clone = image.cloneNode(true);

    clone.style.position = 'fixed';
    clone.style.top = `${rect.top}px`;
    clone.style.left = `${rect.left}px`;
    clone.style.width = `${rect.width}px`;
    clone.style.height = `${rect.height}px`;
    clone.style.margin = '0';
    clone.style.zIndex = String(getProjectCoverTransitionCloneZIndex());
    clone.style.pointerEvents = 'none';
    clone.style.objectFit = computedStyle.objectFit;
    clone.style.transformOrigin = 'top left';

    image.style.visibility = 'hidden';
    document.body.appendChild(clone);

    pendingProjectCoverTransition = {
        href,
        projectKey,
        sourceImage: image,
        clone,
    };
}

function animateProjectCoverTransition(targetImage, targetRectOverride = null, options = {}) {
    if (!pendingProjectCoverTransition || !window.gsap) {
        cleanupPendingProjectCoverTransition();
        return null;
    }

    if (!targetImage) {
        cleanupPendingProjectCoverTransition();
        return null;
    }

    const { clone, sourceImage } = pendingProjectCoverTransition;
    const targetRect = targetRectOverride ?? targetImage.getBoundingClientRect();
    const {
        hideTarget = true,
        removeCloneOnComplete = true,
    } = options;

    pendingProjectCoverTransition.targetImage = targetImage;

    if (hideTarget) {
        targetImage.style.visibility = 'hidden';
    }

    return window.gsap.to(clone, {
        top: targetRect.top,
        left: targetRect.left,
        width: targetRect.width,
        height: targetRect.height,
        duration: 0.55,
        ease: 'power2.inOut',
        onComplete: () => {
            if (removeCloneOnComplete) {
                pendingProjectCoverTransition?.clone?.remove();
            }
        }
    });
}

async function handoffPendingProjectCoverCloneToTarget(options = {}) {
    const clone = pendingProjectCoverTransition?.clone;
    const targetImage = pendingProjectCoverTransition?.targetImage;
    const { fadeOutClone = false } = options;

    if (!clone) {
        return;
    }

    targetImage?.style.removeProperty('visibility');
    await nextFrame();

    if (!fadeOutClone || !window.gsap) {
        clone.remove();
        return;
    }

    await window.gsap.to(clone, {
        opacity: 0,
        duration: 0.1,
        ease: 'power1.out',
        overwrite: 'auto',
        onComplete: () => {
            clone.remove();
        }
    });
}

function getNonSelectedProjectCards(container = document) {
    if (!pendingProjectCoverTransition?.href) {
        return [];
    }

    return Array.from(container.querySelectorAll('.projectCard')).filter((card) => {
        const link = card.querySelector('.projectCardLink');
        return link && link.href !== pendingProjectCoverTransition.href;
    });
}

function getSelectedProjectCard(container = document) {
    if (!pendingProjectCoverTransition) {
        return null;
    }

    const { projectKey, href } = pendingProjectCoverTransition;

    if (projectKey) {
        const byProjectKey = container.querySelector(
            `.projectCardLink[data-project-key="${escapeAttributeValue(projectKey)}"]`
        )?.closest('.projectCard');

        if (byProjectKey) {
            return byProjectKey;
        }
    }

    if (!href) {
        return null;
    }

    return Array.from(container.querySelectorAll('.projectCard')).find((card) => {
        const link = card.querySelector('.projectCardLink');
        return link && link.href === href;
    }) ?? null;
}

function setProjectCardsOpacity(cards, opacity) {
    cards.forEach((card) => {
        card.style.opacity = opacity;
    });
}

function setProjectCardsVisibility(cards, visibility) {
    cards.forEach((card) => {
        card.style.visibility = visibility;
    });
}

function setProjectCardOpacity(card, opacity) {
    if (!card) {
        return;
    }

    card.style.opacity = opacity;
}

function setProjectCardVisibility(card, visibility) {
    if (!card) {
        return;
    }

    card.style.visibility = visibility;
}

function setProjectCardCoverVisibility(card, visibility) {
    if (!card) {
        return;
    }

    const cover = card.querySelector('.projectCover');

    if (!cover) {
        return;
    }

    cover.style.visibility = visibility;
}

function ensureProjectCardVisible(card, options = {}) {
    if (!card) {
        return;
    }

    const { preserveCoverVisibility = false } = options;

    card.style.visibility = 'visible';
    card.style.opacity = '1';
    card.style.removeProperty('willChange');

    const cover = card.querySelector('.projectCover');

    if (!cover) {
        return;
    }

    if (!preserveCoverVisibility) {
        cover.style.removeProperty('visibility');
    }
    cover.style.removeProperty('opacity');
    cover.style.removeProperty('willChange');
}

function getProjectShowFadeTargets(container) {
    if (!container) {
        return [];
    }

    return Array.from(container.querySelectorAll(
        '.projectShowClose, .projectShowInfoUnderCover, .projectShowInfoUnderCoverMobile, .projectShowMeta, .projectShowBottomActions'
    ));
}

function primeProjectShowFadeTargets(container) {
    const targets = getProjectShowFadeTargets(container);

    if (window.gsap) {
        window.gsap.set(targets, {
            autoAlpha: 0,
            y: 12,
        });
        return targets;
    }

    targets.forEach((element) => {
        element.style.opacity = '0';
        element.style.visibility = 'hidden';
        element.style.transform = 'translateY(12px)';
    });

    return targets;
}

function getProjectShowGalleryTargets(container) {
    if (!container) {
        return [];
    }

    return Array.from(container.querySelectorAll('.projectShowGalleryItem'));
}

function getProjectShowGalleryContainer(container) {
    return container?.querySelector('.projectShowGallery') ?? null;
}

function primeProjectShowGalleryContainer(container) {
    const gallery = getProjectShowGalleryContainer(container);

    if (!gallery) {
        return null;
    }

    if (window.gsap) {
        window.gsap.set(gallery, {
            opacity: 0,
        });
        return gallery;
    }

    gallery.style.opacity = '0';
    return gallery;
}

function primeProjectShowGalleryTargets(container) {
    return getProjectShowGalleryTargets(container);
}

async function waitForProjectShowGalleryTargets(targets) {
    await Promise.all(targets.map(async (target) => {
        const images = Array.from(target.querySelectorAll('img'));

        await Promise.all(images.map(async (image) => {
            if (typeof image.decode === 'function') {
                try {
                    await image.decode();
                    return;
                } catch {
                    return;
                }
            }

            if (image.complete) {
                return;
            }

            await new Promise((resolve) => {
                image.addEventListener('load', resolve, { once: true });
                image.addEventListener('error', resolve, { once: true });
            });
        }));
    }));
}

function animateProjectShowFadeTargets(targets) {
    if (!window.gsap || targets.length === 0) {
        return Promise.resolve();
    }

    return window.gsap.fromTo(targets, {
        autoAlpha: 0,
        y: 12,
    }, {
        autoAlpha: 1,
        y: 0,
        duration: 0.5,
        ease: 'power2.out',
        stagger: 0.03,
    });
}

async function animateProjectShowGalleryTargets(targets) {
    if (!window.gsap || targets.length === 0) {
        return Promise.resolve();
    }

    await waitForProjectShowGalleryTargets(targets);
    await nextFrame();

    window.gsap.set(targets, {
        opacity: 0,
    });

    return window.gsap.to(targets, {
        opacity: 1,
        duration: 0.75,
        ease: 'power2.out',
        stagger: 0.12,
        overwrite: 'auto',
        onComplete: () => {
            targets.forEach((target) => {
                target.classList.remove('projectShowGalleryItemPending');
            });
        }
    });
}

function animateProjectShowGalleryContainer(target) {
    if (!window.gsap || !target) {
        return Promise.resolve();
    }

    return window.gsap.to(target, {
        opacity: 1,
        duration: 0.45,
        ease: 'power2.out',
        overwrite: 'auto',
    });
}

async function animateProjectShowGalleryEntrance(targets, container = null) {
    if (!window.gsap || targets.length === 0) {
        if (container) {
            container.style.opacity = '1';
        }

        targets.forEach((target) => {
            target.classList.remove('projectShowGalleryItemPending');
            target.style.removeProperty('opacity');
            target.style.removeProperty('transform');
            target.style.removeProperty('filter');
        });

        return Promise.resolve();
    }

    return Promise.all([
        animateProjectShowGalleryContainer(container),
        animateProjectShowGalleryTargets(targets),
    ]);
}

async function runProjectShowEntrance(container) {
    if (!container || container.dataset.projectShowAnimated === 'true') {
        return;
    }

    const fadeTargets = primeProjectShowFadeTargets(container);
    const gallery = primeProjectShowGalleryContainer(container);
    const galleryTargets = primeProjectShowGalleryTargets(container);

    if (!window.gsap) {
        container.dataset.projectShowAnimated = 'true';
        if (gallery) {
            gallery.dataset.galleryAnimated = 'true';
        }
        return;
    }

    await nextFrame();
    await animateProjectShowFadeTargets(fadeTargets);
    await animateProjectShowGalleryEntrance(galleryTargets, gallery);

    container.dataset.projectShowAnimated = 'true';
    if (gallery) {
        gallery.dataset.galleryAnimated = 'true';
    }
}

function primeInfoPageEntrance(container) {
    const infoPage = container?.querySelector('.infoPage');

    if (!infoPage) {
        return null;
    }

    if (window.gsap) {
        window.gsap.set(infoPage, {
            opacity: 0,
        });
        return infoPage;
    }

    infoPage.style.opacity = '0';
    return infoPage;
}

async function runInfoPageEntrance(container) {
    if (!container || container.dataset.infoPageAnimated === 'true') {
        return;
    }

    const infoPage = primeInfoPageEntrance(container);

    if (!infoPage) {
        return;
    }

    if (!window.gsap) {
        infoPage.style.opacity = '1';
        container.dataset.infoPageAnimated = 'true';
        return;
    }

    await nextFrame();
    await window.gsap.to(infoPage, {
        opacity: 1,
        duration: 0.45,
        ease: 'power2.out',
        overwrite: 'auto',
    });

    container.dataset.infoPageAnimated = 'true';
}

function finalizeHomePageImmediate(container) {
    const grid = container?.querySelector('.projectsGrid');

    if (!grid) {
        return;
    }

    resetHomeGridCardStyles(grid);
    layoutHomeMasonry(grid);
    grid.dataset.homeGridAnimated = 'true';
}

function finalizeProjectShowImmediate(container) {
    if (!container) {
        return;
    }

    const fadeTargets = getProjectShowFadeTargets(container);
    const gallery = getProjectShowGalleryContainer(container);
    const galleryTargets = getProjectShowGalleryTargets(container);

    fadeTargets.forEach((element) => {
        element.style.removeProperty('opacity');
        element.style.removeProperty('visibility');
        element.style.removeProperty('transform');
    });

    if (gallery) {
        gallery.style.removeProperty('opacity');
        gallery.dataset.galleryAnimated = 'true';
    }

    galleryTargets.forEach((target) => {
        target.classList.remove('projectShowGalleryItemPending');
        target.style.removeProperty('opacity');
        target.style.removeProperty('transform');
        target.style.removeProperty('filter');
    });

    container.dataset.projectShowAnimated = 'true';
}

function finalizeInfoPageImmediate(container) {
    if (!container) {
        return;
    }

    const infoPage = container.querySelector('.infoPage');

    if (infoPage) {
        infoPage.style.removeProperty('opacity');
    }

    container.dataset.infoPageAnimated = 'true';
}

async function settleHomeGrid(grid, frames = 2) {
    if (!grid) {
        return;
    }

    for (let index = 0; index < frames; index += 1) {
        await nextFrame();
        layoutHomeMasonry(grid);
    }
}

function getTargetImageForTransition(next, current) {
    if (!pendingProjectCoverTransition) {
        return null;
    }

    if (current.namespace === 'home' && next.namespace === 'project') {
        return next.container.querySelector('.projectShowCover');
    }

    if (current.namespace === 'project' && next.namespace === 'home') {
        const { projectKey, href } = pendingProjectCoverTransition;
        let matchingLink = null;

        if (projectKey) {
            matchingLink = next.container.querySelector(`.projectCardLink[data-project-key="${escapeAttributeValue(projectKey)}"]`);
        }

        if (!matchingLink) {
            matchingLink = Array.from(next.container.querySelectorAll('.projectCardLink'))
                .find((link) => link.href === href);
        }

        return matchingLink?.querySelector('.projectCover') ?? null;
    }

    return null;
}

async function getTargetRectForTransition(next, current, targetImage) {
    if (!pendingProjectCoverTransition || !targetImage) {
        return null;
    }

    let rect = getValidRect(targetImage);

    if (rect) {
        return rect;
    }

    if (current.namespace === 'project' && next.namespace === 'home') {
        await nextFrame();
        layoutHomeMasonry(next.container.querySelector('.projectsGrid'));
        await nextFrame();
        rect = getValidRect(targetImage);
    }

    return rect;
}

function setupBarba() {
    if (barbaStarted || !window.barba || !document.querySelector('[data-barba="wrapper"]')) {
        return;
    }

    barbaStarted = true;

    window.barba.init({
        transitions: [{
            name: 'public-fade',
            async leave({ current }) {
                const isProjectCoverTransition = current.namespace === 'home'
                    || current.namespace === 'project';
                const hasSharedCover = Boolean(pendingProjectCoverTransition?.href);
                const isHomeToProjectTransition = current.namespace === 'home'
                    && hasSharedCover;

                if (!window.gsap) {
                    if (current.namespace === 'info') {
                        await animateInfoNavbarExit();
                    }

                    current.container.style.opacity = '0';
                    return;
                }

                if (isHomeToProjectTransition) {
                    const otherCards = getNonSelectedProjectCards();

                    await Promise.all([
                        window.gsap.to(otherCards, {
                            opacity: 0,
                            duration: 0.45,
                            ease: 'power1.out',
                            stagger: 0.03,
                        }),
                        window.gsap.to(current.container, {
                            opacity: 0,
                            duration: 0.32,
                            ease: 'power1.out',
                            onComplete: () => {
                                pendingProjectCoverTransition?.sourceImage?.style.setProperty('visibility', 'hidden');
                            }
                        })
                    ]);
                    return;
                }

                if (isProjectCoverTransition && hasSharedCover) {
                    await window.gsap.to(current.container, {
                        opacity: 0,
                        duration: 0.32,
                        ease: 'power1.out',
                        onComplete: () => {
                            pendingProjectCoverTransition?.sourceImage?.style.setProperty('visibility', 'hidden');
                        }
                    });
                    return;
                }

                if (current.namespace === 'info') {
                    await Promise.all([
                        animateInfoNavbarExit(),
                        window.gsap.to(current.container, {
                            opacity: 0,
                            duration: 0.2,
                            ease: 'power1.out',
                        })
                    ]);
                    return;
                }

                await window.gsap.to(current.container, {
                    opacity: 0,
                    duration: 0.2,
                    ease: 'power1.out'
                });
            },
            async enter({ current, next }) {
                window.scrollTo(0, 0);
                flushHomeContainerUnpin();
                let unpinNextContainer = () => {};
                let selectedCard = null;
                let homeGrid = null;

                if (next.namespace === 'home') {
                    unpinNextContainer = pinContainerToViewport(next.container);
                    queueHomeContainerUnpin(unpinNextContainer);
                    next.container.style.opacity = '1';
                    next.container.style.visibility = 'hidden';
                    homeGrid = next.container.querySelector('.projectsGrid');
                    await prepareHomeGrid(homeGrid);
                    initPublicPage({ immediate: true });
                    resetHomeGridCardStyles(homeGrid);
                    selectedCard = getSelectedProjectCard(next.container);
                    ensureProjectCardVisible(selectedCard, {
                        preserveCoverVisibility: true,
                    });
                    setProjectCardCoverVisibility(selectedCard, 'hidden');
                    if (current.namespace === 'project') {
                        primeHomeGridReturnCards(homeGrid, selectedCard);
                    } else {
                        primeHomeGridEntrance(homeGrid);
                    }
                    await nextFrame();
                }

                if (next.namespace === 'project') {
                    primeProjectShowFadeTargets(next.container);
                    primeProjectShowGalleryContainer(next.container);
                    primeProjectShowGalleryTargets(next.container);
                }

                if (next.namespace === 'info') {
                    primeInfoPageEntrance(next.container);
                }

                const targetImage = getTargetImageForTransition(next, current);
                const targetRect = await getTargetRectForTransition(next, current, targetImage);
                const isProjectCoverTransition = Boolean(
                    targetImage
                    && targetRect
                    && pendingProjectCoverTransition
                    && (
                        (current.namespace === 'home' && next.namespace === 'project' && pendingProjectCoverTransition.href === next.url.href)
                        || (current.namespace === 'project' && next.namespace === 'home')
                    )
                );

                if (isProjectCoverTransition) {
                    if (current.namespace === 'project' && next.namespace === 'home') {
                        selectedCard = selectedCard ?? targetImage?.closest('.projectCard') ?? null;
                        ensureProjectCardVisible(selectedCard, {
                            preserveCoverVisibility: true,
                        });
                        setProjectCardCoverVisibility(selectedCard, 'hidden');
                    }

                    if (next.namespace !== 'home') {
                        unpinNextContainer();
                    }
                    if (next.namespace === 'home') {
                        next.container.style.visibility = 'hidden';
                    }
                    await settleHomeGrid(homeGrid, 2);
                    await nextFrame();
                    if (next.namespace === 'home') {
                        next.container.style.visibility = 'visible';
                    }
                    if (current.namespace === 'project' && next.namespace === 'home') {
                        setProjectCardVisibility(selectedCard, 'visible');
                        setProjectCardOpacity(selectedCard, '1');
                        setProjectCardCoverVisibility(selectedCard, 'hidden');
                    }
                    await animateProjectCoverTransition(targetImage, targetRect, {
                        hideTarget: true,
                        removeCloneOnComplete: !(current.namespace === 'project' && next.namespace === 'home'),
                    });
                    ensureProjectCardVisible(selectedCard, {
                        preserveCoverVisibility: true,
                    });

                    if (next.namespace === 'home') {
                        await revealHomeGridReturnCards(homeGrid, selectedCard);
                        await handoffPendingProjectCoverCloneToTarget({
                            fadeOutClone: false,
                        });
                    }

                    if (homeGrid) {
                        homeGrid.dataset.homeGridAnimated = 'true';
                    }

                    if (current.namespace === 'home' && next.namespace === 'project') {
                        revealPendingProjectCoverTransitionTarget();
                        return;
                    }

                    revealPendingProjectCoverTransitionTarget();
                    return;
                }

                if (next.namespace !== 'home') {
                    unpinNextContainer();
                }
                if (next.namespace === 'home') {
                    next.container.style.visibility = 'hidden';
                }
                await settleHomeGrid(homeGrid, 2);
                await nextFrame();
                if (next.namespace === 'home') {
                    next.container.style.visibility = 'visible';
                }

                if (next.namespace === 'home') {
                    await revealHomeGridEntrance(homeGrid);
                    homeGrid.dataset.homeGridAnimated = 'true';
                    next.container.style.opacity = '1';
                    return;
                }

                if (!window.gsap) {
                    next.container.style.opacity = '1';
                    return;
                }

                await window.gsap.fromTo(next.container, {
                    opacity: 0
                }, {
                    opacity: 1,
                    duration: 0.25,
                    ease: 'power1.out'
                });
            },
            afterEnter({ next }) {
                if (next.namespace === 'home') {
                    flushHomeContainerUnpin();
                }

                updateTitleFromHtml(next.html);
                updatePersistentNavFromHtml(next.html);

                if (next.namespace !== 'home') {
                    initPublicPage();
                }

                if (next.namespace === 'project') {
                    runProjectShowEntrance(next.container);
                }

                if (next.namespace === 'info') {
                    runInfoPageEntrance(next.container);
                }
            },
            after() {
                flushHomeContainerUnpin();
                revealPendingProjectCoverTransitionTarget();
                cleanupPendingProjectCoverTransition();
            }
        }],
        prevent: ({ el, href }) => {
            if (!el || !href) {
                return true;
            }

            if (el.hasAttribute('download') || el.target === '_blank') {
                return true;
            }

            if (href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('#')) {
                return true;
            }

            return href.includes('/admin') || href.includes('/login') || href.includes('/logout');
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const shouldAnimate = !isAuthenticatedSession();

    if (shouldAnimate) {
        document.addEventListener('click', (event) => {
            const link = event.target.closest('.projectCardLink');
            const projectHomeLink = event.target.closest('a[href]');
            const isProjectPage = document.querySelector('[data-barba-namespace="project"]');

            if (!link) {
                const isProjectCloseLink = projectHomeLink?.classList?.contains('projectShowClose') ?? false;
                const isLegacyHomeLink = projectHomeLink
                    ? new URL(projectHomeLink.href).pathname === '/'
                    : false;

                if (projectHomeLink && isProjectPage && (isProjectCloseLink || isLegacyHomeLink)) {
                    captureProjectCoverTransition({
                        href: window.location.href,
                        image: document.querySelector('.projectShowCover'),
                        projectKey: getProjectKeyFromElement(document.querySelector('.projectShowCover')),
                    });
                }

                return;
            }

            captureProjectCoverTransition({
                href: link.href,
                image: link.querySelector('.projectCover'),
                projectKey: getProjectKeyFromElement(link),
            });
        });
    }

    initPasswordInputs();
    initPublicPage({
        animate: shouldAnimate,
    });

    if (!shouldAnimate) {
        finalizeHomePageImmediate(document.querySelector('[data-barba-namespace="home"]'));
        finalizeProjectShowImmediate(document.querySelector('[data-barba-namespace="project"]'));
        finalizeInfoPageImmediate(document.querySelector('[data-barba-namespace="info"]'));
        return;
    }

    setupBarba();
    runHomeGridEntrance(document.querySelector('[data-barba-namespace="home"]'));
    runProjectShowEntrance(document.querySelector('[data-barba-namespace="project"]'));
    runInfoPageEntrance(document.querySelector('[data-barba-namespace="info"]'));
});
