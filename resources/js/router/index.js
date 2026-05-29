import { createRouter, createWebHistory } from 'vue-router'
import Login from '../pages/login.vue'
import Dashboard from '../pages/Dashboard.vue'
import Vehicules from '../pages/Vehicules.vue'
import Conducteurs from '../pages/Conducteurs.vue'
import Affectations from '../pages/Affectations.vue'
import Maintenances from '../pages/Maintenances.vue'
import Documents from '../pages/Documents.vue'
import Evaluations from '../pages/Evaluations.vue'
import Profil from '../pages/Profil.vue'
import Alertes from '../pages/Alertes.vue'
import PowerBI from '../pages/PowerBI.vue'

const routes = [
    { path: '/',           component: Login },
    { path: '/login',      component: Login },
    { path: '/dashboard',  component: Dashboard, meta: { requiresAuth: true, role: 'Admin' } },
    { path: '/vehicules',  component: Vehicules,  meta: { requiresAuth: true, role: 'Admin' } },
    { path: '/conducteurs',component: Conducteurs,meta: { requiresAuth: true, role: 'Admin' } },
    { path: '/affectations', component: Affectations, meta: { requiresAuth: true } },
    { path: '/maintenances', component: Maintenances, meta: { requiresAuth: true, role: 'Admin' } },
    { path: '/documents', component: Documents, meta: { requiresAuth: true, role: 'Admin' } },
    { path: '/evaluations', component: Evaluations, meta: { requiresAuth: true, role: 'Admin' } },
    { path: '/alertes', component: Alertes, meta: { requiresAuth: true, role: 'Admin' } },
    { path: '/powerbi', component: PowerBI, meta: { requiresAuth: true, role: 'Admin' } },
    { path: '/profil', component: Profil, meta: { requiresAuth: true } },
    { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('token')
    const role = localStorage.getItem('role')

    if (to.meta.requiresAuth && !token) {
        next('/')
    } else if (to.meta.role && to.meta.role !== role) {
        next('/profil') 
    } else {
        next()
    }
})

export default router