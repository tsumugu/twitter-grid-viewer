import Vue from 'vue'
import App from './App.vue'
import './registerServiceWorker'
import router from './router'
import VueLazyload from 'vue-lazyload'
import Vue2TouchEvents from 'vue2-touch-events'

Vue.config.productionTip = false

import { initializeApp } from "firebase/app";
import { getAnalytics } from "firebase/analytics";
const firebaseConfig = {
  apiKey: "AIzaSyC95j9B3Z86zhANfJOMZDcoMsvqB0god-0",
  authDomain: "twittergridviewer.firebaseapp.com",
  projectId: "twittergridviewer",
  storageBucket: "twittergridviewer.appspot.com",
  messagingSenderId: "851119238722",
  appId: "1:851119238722:web:247977ed096f97221987db",
  measurementId: "${config.measurementId}"
};
const app = initializeApp(firebaseConfig);
getAnalytics(app);

Vue.use(Vue2TouchEvents)
Vue.use(VueLazyload)
/*
Vue.use(VueLazyload, {
  preLoad: 1.3,
  error: 'dist/error.png',
  loading: 'dist/loading.gif',
  attempt: 1
})
*/
new Vue({
  router,
  render: h => h(App)
}).$mount('#app')
