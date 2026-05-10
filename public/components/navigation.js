import { store } from './store.js';

export default {
  props: {},
  data() {
    return {
      store,
    };
  },
  methods: {},
  template: `<nav v-if="store.isLoggedIn">
              <div class="nav-item button" @click="store.setPage(1)">
                <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#ffffff"><path d="M528-624v-192h288v192H528ZM144-432v-384h288v384H144Zm384 288v-384h288v384H528Zm-384 0v-192h288v192H144Zm72-360h144v-240H216v240Zm384 288h144v-240H600v240Zm0-479h144v-49H600v49ZM216-216h144v-48H216v48Zm144-288Zm240-191Zm0 239ZM360-264Z"/></svg>
                Dashboard
              </div>
              <div class="nav-item button" @click="store.setPage(2)">
                <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#ffffff"><path d="M643-216 507-352l51-51 84 85 170-170 52 51-221 221Zm0-312L507-664l51-51 85 85 169-170 52 51-221 221ZM96-288v-72h336v72H96Zm0-312v-72h336v72H96Z"/></svg>
                Tasks
              </div>
              <div v-if="store.currentUser.role == 'admin'" class="nav-item button" @click="store.setPage(3)">
                <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#ffffff"><path d="M287-527q-47-47-47-113t47-113q47-47 113-47t113 47q47 47 47 113t-47 113q-47 47-113 47t-113-47ZM80-160v-112q0-33 17-62t47-44q51-26 115-44t141-18h14q6 0 12 2-8 18-13.5 37.5T404-360h-4q-71 0-127.5 18T180-306q-9 5-14.5 14t-5.5 20v32h252q6 21 16 41.5t22 38.5H80Zm560 40-12-60q-12-5-22.5-10.5T584-204l-58 18-40-68 46-40q-2-14-2-26t2-26l-46-40 40-68 58 18q11-8 21.5-13.5T628-460l12-60h80l12 60q12 5 22.5 11t21.5 15l58-20 40 70-46 40q2 12 2 25t-2 25l46 40-40 68-58-18q-11 8-21.5 13.5T732-180l-12 60h-80Zm96.5-143.5Q760-287 760-320t-23.5-56.5Q713-400 680-400t-56.5 23.5Q600-353 600-320t23.5 56.5Q647-240 680-240t56.5-23.5Zm-280-320Q480-607 480-640t-23.5-56.5Q433-720 400-720t-56.5 23.5Q320-673 320-640t23.5 56.5Q367-560 400-560t56.5-23.5ZM400-640Zm12 400Z" /></svg>
                Users
              </div>
              <div v-if="store.currentUser.role == 'admin'" class="nav-item button" @click="store.setPage(4)">
                <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#ffffff"><path d="m403-96-22-114q-23-9-44.5-21T296-259l-110 37-77-133 87-76q-2-12-3-24t-1-25q0-13 1-25t3-24l-87-76 77-133 110 37q19-16 40.5-28t44.5-21l22-114h154l22 114q23 9 44.5 21t40.5 28l110-37 77 133-87 76q2 12 3 24t1 25q0 13-1 25t-3 24l87 76-77 133-110-37q-19 16-40.5 28T579-210L557-96H403Zm59-72h36l19-99q38-7 71-26t57-48l96 32 18-30-76-67q6-17 9.5-35.5T696-480q0-20-3.5-38.5T683-554l76-67-18-30-96 32q-24-29-57-48t-71-26l-19-99h-36l-19 99q-38 7-71 26t-57 48l-96-32-18 30 76 67q-6 17-9.5 35.5T264-480q0 20 3.5 38.5T277-406l-76 67 18 30 96-32q24 29 57 48t71 26l19 99Zm18-168q60 0 102-42t42-102q0-60-42-102t-102-42q-60 0-102 42t-42 102q0 60 42 102t102 42Zm0-144Z"/></svg>
                Settings
              </div>
            </nav>`,
};
