import { createApp } from 'https://unpkg.com/petite-vue?module';
import { todoStorage } from './storage.js';
import { filters } from './filters.js';

export default createApp({
  user: userStorage.fetch(),
  todos: todoStorage.fetch(),
  newTodo: '',
  editedTodo: null,
  visibility: 'all',

  get filteredTodos() {
    return filters[this.visibility](this.todos);
  },

  get remaining() {
    return filters.active(this.todos).length;
  },

  get allDone() {
    return this.remaining === 0;
  },

  set allDone(value) {
    this.todos.forEach(function (todo) {
      todo.completed = value;
    });
  },

  save() {
    todoStorage.save(this.todos);
  },

  addTodo() {
    var value = this.newTodo && this.newTodo.trim();
    if (!value) {
      return;
    }
    this.todos.push({
      id: todoStorage.uid++,
      title: value,
      completed: false,
    });
    this.newTodo = '';
  },

  removeTodo(todo) {
    this.todos.splice(this.todos.indexOf(todo), 1);
  },

  editTodo(todo) {
    this.beforeEditCache = todo.title;
    this.editedTodo = todo;
  },

  doneEdit(todo) {
    if (!this.editedTodo) {
      return;
    }
    this.editedTodo = null;
    todo.title = todo.title.trim();
    if (!todo.title) {
      this.removeTodo(todo);
    }
  },

  cancelEdit(todo) {
    this.editedTodo = null;
    todo.title = this.beforeEditCache;
  },

  removeCompleted() {
    this.todos = filters.active(this.todos);
  },
});
