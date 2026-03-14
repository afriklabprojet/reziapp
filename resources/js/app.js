import './bootstrap';

import Alpine from 'alpinejs';
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
import { residencePage, bookingForm, initResidenceMap } from './pages/residence-show';
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

// Register Alpine components before start
window.Alpine = Alpine;

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
Alpine.data('bookingForm', (config) => bookingForm(config));
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

// Expose globals needed by some pages
window.initBookingStore = initBookingStore;
window.initResidenceMap = initResidenceMap;
window.searchPage = searchPage;
window.ownerStatisticsChart = (dailyStats) => ownerStatisticsChart({ dailyStats });
window.clientStatisticsCharts = (config) => clientStatisticsCharts(config);
window.initSponsoredPerformanceChart = (data) => initSponsoredPerformanceChart(data);
window.initFiscalChart = (data) => initFiscalChart(data);
window.toggleHelpful = toggleHelpful;
window.openReportModal = openReportModal;
window.toggleFavorite = compareToggleFavorite;
window.requestNotificationPermission = requestNotificationPermission;
window.markAsRead = markAsRead;
window.copyToClipboard = copyToClipboard;
window.copyShareLink = copyShareLink;
window.confirmEmergency = confirmEmergency;

Alpine.start();
