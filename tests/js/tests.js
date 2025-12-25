// Registry of all test files
// Since browsers can't scan directories, we must manually import tests here.

import * as Vue from 'vue';
window.Vue = Vue;

import './samples/ButtonTest.js';
import './ui/ToastContainerTest.js';
import './ui/NavBarTest.js';
import './ui/ConfirmModalTest.js';
import './admin/LoginTest.js';
import './admin/AdminDashboardTest.js';
import './plugins/Todo/TodoListTest.js';
import './cms/CMSTest.js';
import './admin/settings/UsersAdminTest.js';
import './admin/settings/PluginsAdminTest.js';
import './admin/settings/UserSettingsTest.js';
import './admin/settings/SiteSettingsTest.js';
