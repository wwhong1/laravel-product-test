import { createRouter, createWebHistory } from 'vue-router'
import Login from '../views/Login.vue'
import Products from '../views/Products.vue'

const routes = [
  { path: '/login', component: Login },
  {
    path: '/',
    component: Products,
    beforeEnter: (to, from, next) => {
      localStorage.getItem('token') ? next() : next('/login')
    }
  },
  { path: '/:pathMatch(.*)*', redirect: '/' }
]

export default createRouter({ history: createWebHistory(), routes })
