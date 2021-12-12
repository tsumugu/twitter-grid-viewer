import Vue from 'vue'
import App from './App.vue'
import './registerServiceWorker'
import router from './router'

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

new Vue({
  router,
  render: h => h(App)
}).$mount('#app')
