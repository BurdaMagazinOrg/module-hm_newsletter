/**
 * @file
 */

(function ($, window, document) {
  Drupal.behaviors.thsixty = {
    attach: function (context) {
      // Check if "thsixtyQ" is already loaded.
      if (window.thsixtyQ) {
        return;
      }

      var config = {
        env: drupalSettings.hm_newsletter.env,
        version: 'v1'
      };
      window.thsixtyQ = window.thsixtyQ || [];
      window.thsixtyQ.push(['init', {config: config}]);
      var th = document.createElement('script');
      th.type = 'text/javascript';
      th.async = true;
      if (drupalSettings.hm_newsletter.env === 'staging') {
        th.src = '//d2528hoa8g0iaj.cloudfront.net/staging/thsixty.min.js';
      }
      else {
        th.src = '//d2528hoa8g0iaj.cloudfront.net/thsixty.min.js';
      }
      var s = document.getElementsByTagName('script')[0];
      s.parentNode.insertBefore(th, s);
    }
  };
})(jQuery, window, document);
