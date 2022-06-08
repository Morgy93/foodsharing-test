import { vueRegister, vueApply } from '@/vue'

import './Topbar.scss'
import Topbar from './Topbar'

vueRegister({ Topbar })
vueApply('#vue-topbar')
