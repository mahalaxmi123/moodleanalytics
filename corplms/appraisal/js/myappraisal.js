/*
 * This file is part of Corplms LMS
 *
 * Copyright (C) 2010 onwards Corplms Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark Webster <mark.webster@catalyst-eu.net>
 * @package corplms
 * @subpackage corplms_question
 */

M.corplms_appraisal_myappraisal = M.corplms_appraisal_myappraisal || {
  Y : null,

  config: {},
  /**
   * module initialisation method called by php js_init_call()
   *
   * @param object    YUI instance
   * @param string    args supplied in JSON format
   */
  init : function(Y, args) {
    // save a reference to the Y instance (all of its dependencies included)
    this.Y = Y;

    if (args) {
      var jargs = Y.JSON.parse(args);
      for (var a in jargs) {
        if (Y.Object.owns(jargs, a)) {
          this.config[a] = jargs[a];
        }
      }
    }

    // check jQuery dependency is available
    if ( typeof $ === 'undefined') {
      throw new Error('M.corplms_appraisal_myappraisal.init()-> jQuery dependency required for this module to function.');
    }

    var $mainForm = $('input#id_submitbutton').closest('form');
    var $saveProgress = $("<input>").attr({"type" : "hidden", "name" : "submitaction"}).val('saveprogress');
    var $completeStage = $("<input>").attr({"type" : "hidden", "name" : "submitaction"}).val('completestage');

    $('#saveprogress').on('submit', function(e){
      window.onbeforeunload = null; // Prevent leaving page warning.
      e.preventDefault();
      $mainForm.append($saveProgress);
      $mainForm.submit();
    });

    $('#completestage').on('submit', function(e){
      window.onbeforeunload = null; // Prevent leaving page warning.
      e.preventDefault();
      $mainForm.append($completeStage);
      $mainForm.submit();
    });

    // Print and PDF dialog boxes
    var snapshoturl = M.cfg.wwwroot+'/corplms/appraisal/snapshot.php';
    (function(args) {

        var urlparam = {
            appraisalid: args.appraisalid,
            role: args.role,
            subjectid: args.subjectid,
            action: 'stages'
        }
        var urlparamstr = $.param(urlparam);

        M.corplms_appraisal_myappraisal.stagesSelectDialog(
            'print',
            M.util.get_string('printyourappraisal', 'corplms_appraisal'),
            snapshoturl+'?'+urlparamstr,
            snapshoturl
        );
    })(this.config);

    (function(args) {

        var urlparam = {
            appraisalid: args.appraisalid,
            role: args.role,
            subjectid: args.subjectid,
            action: 'snapshot'
        }
        var urlparamstr = $.param(urlparam);

        M.corplms_appraisal_myappraisal.savePdfDialog(
            'savepdf',
            M.util.get_string('snapshotdialogtitle', 'corplms_appraisal'),
            snapshoturl+'?'+urlparamstr
        );
    })(this.config);

    (function(args) {
      var keepAliveInterval = setInterval(function() {M.corplms_appraisal_myappraisal.keepAlive(); }, 1000 * args.keepalivetime);
    })(this.config);
  },

    stagesSelectDialog: function(name, title, findurl, printurl) {
        var handler = new corplmsDialog_handler();

        handler._print = function(e, printurl) {
            var urlparam = $('#printform').serialize();

            M.util.help_popups.setup(Y);
            popupdata = {
                name: 'printpopup',
                url: printurl+'?'+urlparam,
                options: "height=500,width=600,top=100,left=100,menubar=0,location=0,scrollbars,resizable,toolbar,status,directories=0,dependent"
            }
            openpopup(e, popupdata);

            this._cancel();
        }

        var buttonObj = {};
        buttonObj[M.util.get_string('cancel', 'moodle')] = function() { handler._cancel() };
        buttonObj[M.util.get_string('printnow', 'corplms_appraisal')] = function(e) { handler._print(e, printurl) };

        corplmsDialogs[name] = new corplmsDialog(
            name,
            'show-'+name+'-dialog',
            {
                buttons: buttonObj,
                title: '<h2>'+title+'</h2>'
            },
            findurl,
            handler
        );
    },

    savePdfDialog: function(name, title, findurl) {
        var handler = new corplmsDialog_handler();

        handler._download = function() {
            var url = $('#downloadurl').val();
            if (url) {
                window.location.href = url;
                this._cancel();
            }
        }

        handler._open = function() {
            this._dialog.dialog.html(M.util.get_string('snapshotgeneration', 'corplms_appraisal'));
        }

        var buttonObj = {};
        buttonObj[M.util.get_string('cancel', 'moodle')] = function() { handler._cancel() };
        buttonObj[M.util.get_string('downloadnow', 'corplms_appraisal')] = function() { handler._download() };

        corplmsDialogs[name] = new corplmsDialog(
            name,
            'show-'+name+'-dialog',
            {
                buttons: buttonObj,
                title: '<h2>'+title+'</h2>',
                height: '200'
            },
            findurl,
            handler
        );
    },

    keepAlive: function() {
        $.get(M.cfg.wwwroot + '/corplms/appraisal/keepalive.php');
    }
}
