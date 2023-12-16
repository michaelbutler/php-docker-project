import $ from "jquery";
import '@/js/bootstrap/bootstrap.bundle.js';
import '@/styles/index.scss'

/*
webpack entrypoint file
 */
$(function ($) {
    // Run global jsFunction list & setup jQuery globally
    if (window.jsFunctions) {
        // Assign jQuery globally
        window.jQuery = $;
        window.jsFunctions.forEach(item => item());
    }

});
