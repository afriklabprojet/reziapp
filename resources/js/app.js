import './bootstrap';

import Alpine from '@alpinejs/csp';
import intersect from '@alpinejs/intersect';
import collapse from '@alpinejs/collapse';

// Import extracted Alpine components
import mapSearch from './components/map-search';
import pushNotifications from './components/push-notifications';
import filterModal from './components/mobile-filters';
import favoritesManager from './components/favorites-manager';
import favoriteButton from './components/favorite-button';
import chatApp from './pages/conversation-show';
import chatShow from './pages/chat-show';
import { residencePage, bookingForm, reviewPager, initResidenceMap } from './pages/residence-show';
import { nearbyPOI, directionsWidget, streetViewWidget, isochroneWidget, stickyNav } from './pages/residence-show-maps';
import bookingCreateForm, { initBookingStore } from './pages/booking-create';
import searchPage from './pages/residence-search';
import residenceWizard from './pages/residence-wizard';
import notificationPreferences from './pages/notification-preferences';
import pricingCalendar from './pages/owner-pricing';
import residenceEditForm, { photoUploader } from './pages/residence-edit';
import paymentForm from './pages/payment-checkout';
import templatesManager from './pages/templates-index';
import ownerStatisticsChart from './pages/owner-statistics';
import clientStatisticsCharts from './pages/client-statistics';
import analyticsPage from './pages/owner-analytics';
import bookingCalendar from './pages/owner-booking-calendar';
import residenceIndex from './pages/residence-index';
import lazyImage from './components/lazy-image';
import searchForm from './components/search-form';
import ownerDashboard from './pages/owner-dashboard';
import priceSuggestions from './pages/price-suggestions';
import sponsoredForm from './pages/sponsored-create';
import promotionCreate from './pages/promotion-create';
import couponCreate from './pages/coupon-create';
import initSponsoredPerformanceChart from './pages/sponsored-show';
import initFiscalChart from './pages/fiscal-chart';
import { toggleHelpful, openReportModal } from './components/review-card';
import contactsIndex from './pages/contacts-index';
import addressAutocomplete from './components/address-autocomplete';
import { toggleFavorite as compareToggleFavorite } from './pages/compare';
import { requestNotificationPermission } from './pages/alerts';
import { markAsRead } from './pages/notifications-index';
import autoReplyForm from './pages/auto-reply-form';
import reviewForm from './pages/review-create';
import { copyToClipboard, copyShareLink } from './components/clipboard';
import supportShow from './pages/support-show';
import ownerResidenceMap from './pages/owner-residence-show';
import { confirmEmergency } from './pages/verification-dashboard';
import recommendationStrip from './components/recommendation-strip';
import chatbot from './components/chatbot';
import homeHero from './pages/home-hero';
import selfieCapture from './pages/selfie-capture';
import residenceCalendar from './components/residence-calendar';
import citySelector from './components/city-selector';
import earningsPayout from './pages/earnings-payout';
import couponShow from './pages/coupon-show';
import { leaseTenantSearch, leaseTypeSection, leaseClausesSection } from './pages/lease-contract-create';
import photoUploadCreate from './components/photo-uploader';
import residenceCreateForm from './pages/residence-create';
import stickyBookingBar from './components/sticky-booking-bar';
import residenceMap from './pages/residence-map';
import promotionForm from './pages/promotion-form';
import languageSelector from './components/language-selector';
import otpInputForm from './components/otp-input';
import mobileHeader from './components/mobile-header';
import { navigationState, newsletterForm, scrollReveal, themeToggle } from './components/ui-state';

// Register Alpine components before start
globalThis.Alpine = Alpine;

// Register Alpine plugins
Alpine.plugin(intersect);
Alpine.plugin(collapse);

// Register as Alpine.data for x-data="componentName(config)" usage
Alpine.data('mapSearch', (config) => mapSearch(config));
Alpine.data('pushNotifications', () => pushNotifications());
Alpine.data('filterModal', () => filterModal());
Alpine.data('favoritesManager', (config) => favoritesManager(config));
Alpine.data('favoriteButton', (config) => favoriteButton(config));
Alpine.data('chatApp', (config) => chatApp(config));
Alpine.data('chatShow', (config) => chatShow(config));
Alpine.data('residencePage', (config) => residencePage(config));
Alpine.data('nearbyPOI', (id, base) => nearbyPOI(id, base));
Alpine.data('directionsWidget', (id, base) => directionsWidget(id, base));
Alpine.data('streetViewWidget', (id, base) => streetViewWidget(id, base));
Alpine.data('isochroneWidget', (id, base, lat, lng, name) => isochroneWidget(id, base, lat, lng, name));
Alpine.data('stickyNav', () => stickyNav());
Alpine.data('bookingForm', (config) => bookingForm(config));
Alpine.data('reviewPager', (url, initial) => reviewPager(url, initial));
Alpine.data('bookingCreateForm', (config) => bookingCreateForm(config));
Alpine.data('residenceWizard', (config) => residenceWizard(config));
Alpine.data('notificationPreferences', (config) => notificationPreferences(config));
Alpine.data('pricingCalendar', (config) => pricingCalendar(config));
Alpine.data('residenceEditForm', (config) => residenceEditForm(config));
Alpine.data('photoUploader', () => photoUploader());
Alpine.data('paymentForm', (config) => paymentForm(config));
Alpine.data('templatesManager', (config) => templatesManager(config));
Alpine.data('analyticsPage', (config) => analyticsPage(config));
Alpine.data('bookingCalendar', (config) => bookingCalendar(config));
Alpine.data('recommendationStrip', (userId, residences, limit) => recommendationStrip(userId, residences, limit));
Alpine.data('chatbot', (config) => chatbot(config));
Alpine.data('residenceIndex', (config) => residenceIndex(config));
Alpine.data('lazyImage', (src, blurSrc) => lazyImage(src, blurSrc));
Alpine.data('searchForm', (config) => searchForm(config));
Alpine.data('ownerDashboard', (config) => ownerDashboard(config));
Alpine.data('priceSuggestions', (config) => priceSuggestions(config));
Alpine.data('sponsoredForm', (config) => sponsoredForm(config));
Alpine.data('promotionCreate', () => promotionCreate());
Alpine.data('couponCreate', () => couponCreate());
Alpine.data('contactsIndex', () => contactsIndex());
Alpine.data('autoReplyForm', (config) => autoReplyForm(config));
Alpine.data('reviewForm', (config) => reviewForm(config));
Alpine.data('supportShow', () => supportShow());
Alpine.data('ownerResidenceMap', (config) => ownerResidenceMap(config));
Alpine.data('searchPage', (config) => searchPage(config));
Alpine.data('addressAutocomplete', () => addressAutocomplete());
Alpine.data('homeHero', (config) => homeHero(config));
Alpine.data('selfieCapture', () => selfieCapture());
Alpine.data('residenceCalendar', (config) => residenceCalendar(config));
Alpine.data('citySelector', (config) => citySelector(config));
Alpine.data('earningsPayout', () => earningsPayout());
Alpine.data('couponShow', (config) => couponShow(config));
Alpine.data('leaseTenantSearch', (config) => leaseTenantSearch(config));
Alpine.data('leaseTypeSection', (config) => leaseTypeSection(config));
Alpine.data('leaseClausesSection', (config) => leaseClausesSection(config));
Alpine.data('photoUploadCreate', () => photoUploadCreate());
Alpine.data('residenceCreateForm', (config) => residenceCreateForm(config));
Alpine.data('stickyBookingBar', (config) => stickyBookingBar(config));
Alpine.data('residenceMap', (config) => residenceMap(config));
Alpine.data('promotionForm', (config) => promotionForm(config));
Alpine.data('languageSelector', (config) => languageSelector(config));
Alpine.data('otpInputForm', () => otpInputForm());
Alpine.data('mobileHeader', (transparent = false) => mobileHeader(Boolean(transparent)));
Alpine.data('themeToggle', () => themeToggle());
Alpine.data('newsletterForm', (subscribeUrl, csrfToken) => newsletterForm(subscribeUrl, csrfToken));
Alpine.data('navigationState', (threshold = 8) => navigationState(Number(threshold)));
Alpine.data('scrollReveal', (threshold = 400) => scrollReveal(Number(threshold)));
Alpine.data('autoHide', (delay = 4000) => ({
    show: true,
    init() { setTimeout(() => { this.show = false; }, delay); },
}));
Alpine.data('clientDashboard', () => ({
    loaded: false,
    init() {
        this.$nextTick(() => {
            setTimeout(() => { this.loaded = true; }, 80);
        });
    },
}));

// Expose globals needed by some pages
globalThis.initBookingStore = initBookingStore;
globalThis.initResidenceMap = initResidenceMap;
globalThis.searchPage = searchPage;
globalThis.ownerStatisticsChart = (dailyStats) => ownerStatisticsChart({ dailyStats });
globalThis.clientStatisticsCharts = (config) => clientStatisticsCharts(config);
globalThis.initSponsoredPerformanceChart = (data) => initSponsoredPerformanceChart(data);
globalThis.initFiscalChart = (data) => initFiscalChart(data);
globalThis.toggleHelpful = toggleHelpful;
globalThis.openReportModal = openReportModal;
globalThis.toggleFavorite = compareToggleFavorite;
globalThis.requestNotificationPermission = requestNotificationPermission;
globalThis.markAsRead = markAsRead;
globalThis.copyToClipboard = copyToClipboard;
globalThis.copyShareLink = copyShareLink;
globalThis.confirmEmergency = confirmEmergency;

Alpine.start();

// CSP-safe confirm handler (replaces onsubmit/onclick="return confirm(...)")
document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('submit', (e) => {
        const form = e.target;
        const msg = form.dataset.confirm
            ?? form.querySelector('[type=submit][data-confirm]')?.dataset.confirm;
        if (msg && !window.confirm(msg)) {
            e.preventDefault();
        }
    }, true);
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-confirm], a[data-confirm]');
        if (btn && !window.confirm(btn.dataset.confirm)) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    }, true);
});
