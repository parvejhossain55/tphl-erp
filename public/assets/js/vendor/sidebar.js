"use strict";

const ANIMATION_DURATION = 300;
const sidebar = document.getElementById("sidebar");
let mainContentDiv = document.querySelector(".app__slide-wrapper");
const slideHasSub = document.querySelectorAll(".nav > ul > .slide.has-sub");
const firstLevelItems = document.querySelectorAll(".nav > ul > .slide.has-sub > a");
const innerLevelItems = document.querySelectorAll(".nav > ul > .slide.has-sub .slide.has-sub > a");

class PopperObject {
  instance = null;
  reference = null;
  popperTarget = null;

  constructor(reference, popperTarget) {
    this.init(reference, popperTarget);
  }

  init(reference, popperTarget) {
    this.reference = reference;
    this.popperTarget = popperTarget;
    if (!this.popperTarget) return;

    if (typeof Popper !== "undefined" && typeof Popper.createPopper === "function") {
      try {
        this.instance = Popper.createPopper(this.reference, this.popperTarget, {
          placement: "bottom",
          strategy: "relative",
          resize: true,
          modifiers: [
            {
              name: "computeStyles",
              options: {
                adaptive: false,
              },
            },
          ],
        });

        document.addEventListener("click", (e) => this.clicker(e, this.popperTarget, this.reference), false);

        const ro = new ResizeObserver(() => {
          if (this.instance) this.instance.update();
        });

        ro.observe(this.popperTarget);
        ro.observe(this.reference);
      } catch (err) {
        console.warn("Failed to initialize Popper instance:", err);
      }
    }
  }

  clicker(event, popperTarget, reference) {
    if (
      sidebar &&
      sidebar.classList.contains("collapsed") &&
      popperTarget &&
      !popperTarget.contains(event.target) &&
      !reference.contains(event.target)
    ) {
      this.hide();
    }
  }

  hide() {
    // this.instance.state.elements.popper.style.visibility = "hidden";
  }
}

class Poppers {
  subMenuPoppers = [];

  constructor() {
    try {
      this.init();
    } catch (err) {
      console.warn("Poppers constructor init failed:", err);
    }
  }

  init() {
    slideHasSub.forEach((element) => {
      const target = element.lastElementChild;
      if (target) {
        this.subMenuPoppers.push(new PopperObject(element, target));
      }
    });
    this.closePoppers();
  }

  togglePopper(target) {
    if (target && window.getComputedStyle(target).visibility === "hidden") {
      target.style.visibility = "visible";
    } else if (target) {
      target.style.visibility = "hidden";
    }
  }

  updatePoppers() {
    this.subMenuPoppers.forEach((element) => {
      if (element.instance && element.instance.state && element.instance.state.elements && element.instance.state.elements.popper) {
        element.instance.state.elements.popper.style.display = "none";
        element.instance.update();
      }
    });
  }

  closePoppers() {
    this.subMenuPoppers.forEach((element) => {
      element.hide();
    });
  }
}

const slideUp = (target, duration = ANIMATION_DURATION) => {
  if (!target) return;
  const { parentElement } = target;
  if (parentElement) parentElement.classList.remove("open");
  target.style.transitionProperty = "height, margin, padding";
  target.style.transitionDuration = `${duration}ms`;
  target.style.boxSizing = "border-box";
  target.style.height = `${target.offsetHeight}px`;
  target.offsetHeight;
  target.style.overflow = "hidden";
  target.style.height = 0;
  target.style.paddingTop = 0;
  target.style.paddingBottom = 0;
  target.style.marginTop = 0;
  target.style.marginBottom = 0;
  window.setTimeout(() => {
    target.style.display = "none";
    target.style.removeProperty("height");
    target.style.removeProperty("padding-top");
    target.style.removeProperty("padding-bottom");
    target.style.removeProperty("margin-top");
    target.style.removeProperty("margin-bottom");
    target.style.removeProperty("overflow");
    target.style.removeProperty("transition-duration");
    target.style.removeProperty("transition-property");
  }, duration);
};

const slideDown = (target, duration = ANIMATION_DURATION) => {
  if (!target) return;
  const { parentElement } = target;
  if (parentElement) parentElement.classList.add("open");
  target.style.removeProperty("display");
  let { display } = window.getComputedStyle(target);
  if (display === "none") display = "block";
  target.style.display = display;
  const height = target.offsetHeight;
  target.style.overflow = "hidden";
  target.style.height = 0;
  target.style.paddingTop = 0;
  target.style.paddingBottom = 0;
  target.style.marginTop = 0;
  target.style.marginBottom = 0;
  target.offsetHeight;
  target.style.boxSizing = "border-box";
  target.style.transitionProperty = "height, margin, padding";
  target.style.transitionDuration = `${duration}ms`;
  target.style.height = `${height}px`;
  target.style.removeProperty("padding-top");
  target.style.removeProperty("padding-bottom");
  target.style.removeProperty("margin-top");
  target.style.removeProperty("margin-bottom");
  window.setTimeout(() => {
    target.style.removeProperty("height");
    target.style.removeProperty("overflow");
    target.style.removeProperty("transition-property");
    target.style.removeProperty("transition-duration");
  }, duration);
};

const slideToggle = (target, duration = ANIMATION_DURATION) => {
  if (!target) return;
  if (window.getComputedStyle(target).display === "none") {
    return slideDown(target, duration);
  }
  return slideUp(target, duration);
};

let PoppersInstance = null;
try {
  PoppersInstance = new Poppers();
} catch (e) {
  console.warn("Could not initialize PoppersInstance:", e);
}

const updatePoppersTimeout = () => {
  setTimeout(() => {
    if (PoppersInstance) PoppersInstance.updatePoppers();
  }, ANIMATION_DURATION);
};

const defaultOpenMenus = document.querySelectorAll(".slide.has-sub.open");
defaultOpenMenus.forEach((element) => {
  if (element.lastElementChild) {
    element.lastElementChild.style.display = "block";
  }
});

firstLevelItems.forEach((element) => {
  element.addEventListener("click", () => {
    const parentMenu = element.closest(".nav.sub-open");
    if (parentMenu)
      parentMenu.querySelectorAll(":scope > ul > .slide.has-sub > a").forEach((el) => {
        if (el.nextElementSibling && (el.nextElementSibling.style.display === "block" || window.getComputedStyle(el.nextElementSibling).display === "block")) {
          slideUp(el.nextElementSibling);
        }
      });
    if (element.nextElementSibling) {
      slideToggle(element.nextElementSibling);
    }
  });
});

innerLevelItems.forEach((element) => {
  element.addEventListener("click", () => {
    const innerMenu = element.closest(".sidebar-menu");
    if (innerMenu)
      innerMenu.querySelectorAll(":scope .slide.has-sub > a").forEach((el) => {
        if (el.nextElementSibling && el.nextElementSibling?.style.display === "block") {
          slideUp(el.nextElementSibling);
        }
      });
    if (element.nextElementSibling) {
      slideToggle(element.nextElementSibling);
    }
  });
});

const setNavActive = () => {
  // Expand all submenus by default on page load
  const allSubMenus = document.querySelectorAll(".slide.has-sub");
  allSubMenus.forEach((subMenu) => {
    subMenu.classList.add("open");
    const subUl = subMenu.querySelector("ul.sidebar-menu");
    if (subUl) {
      subUl.style.display = "block";
    }
    const subLink = subMenu.querySelector(":scope > a");
    if (subLink) {
      subLink.classList.add("menu-open");
    }
  });

  // Find active items (which PHP sets on the specific .sidebar__menu-item)
  const activeItems = document.querySelectorAll(".sidebar__menu-item.active");
  activeItems.forEach((activeItem) => {
    // Traverse up to find parent submenus and highlight them as active
    let parent = activeItem.parentElement;
    while (parent && parent !== sidebar) {
      if (parent.classList.contains("slide") && parent.classList.contains("has-sub")) {
        parent.classList.add("active");
        const triggerLink = parent.querySelector(":scope > a");
        if (triggerLink) {
          triggerLink.classList.add("active");
        }
      }
      parent = parent.parentElement;
    }
  });
};

setNavActive();

/* for menu scroll to top active page */
let customScrollTop = () => {
  // Prefer the sub-menu active item (which doesn't have href="javascript:void(0);")
  let activeEle = document.querySelector(".sidebar__menu-item.active:not([href^='javascript:'])");
  if (!activeEle) {
    activeEle = document.querySelector(".sidebar__menu-item.active");
  }
  if (activeEle) {
    let elemRect = activeEle.getBoundingClientRect();
    if (elemRect.top > 450 || elemRect.top < 0) {
      activeEle.scrollIntoView({ behavior: "smooth", block: "center" });
    }
  }
};
setTimeout(() => {
  customScrollTop();
}, 300);