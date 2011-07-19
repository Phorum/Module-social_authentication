<?php
if (empty($PHORUM['DATA']['AUTHENTICATION_PROVIDER'])) {
  $PHORUM['DATA']['AUTHENTICATION_PROVIDER'] =
      isset($_COOKIE['sa_provider'])
      ? addslashes($_COOKIE['sa_provider']) : null;
}
$PHORUM['DATA']['AUTHENTICATION_AUTHID'] =
    isset($_COOKIE['sa_authid'])
    ? addslashes($_COOKIE['sa_authid']) : '';
?>
<form action="{URL->ACTION}" method="post" style="display:none" id="idselector">

  {POST_VARS}

  <input type="hidden"
         name="_sap"
         id="social_authentication_provider"
         value="{IF AUTHENTICATION_PROVIDER}{AUTHENTICATION_PROVIDER}{/IF}" />

  <div class="generic social_authentication_login" style="margin-top: 1em">

    <h4>{LANG->mod_social_authentication->LoginTitle}</h4>

    {IF SOCIAL_AUTHENTICATION_ERROR}
      <div id="social_authentication_error" style="display:block">
        {SOCIAL_AUTHENTICATION_ERROR}
      </div>
    {ELSE}
      <div id="social_authentication_error"></div>
    {/IF}

    <div class="social_authentication_providers">

      {LOOP AUTHPROVIDERS}
        {IF AUTHPROVIDERS->active}
          <div class="social_authentication_provider provider_{AUTHPROVIDERS->id}">
            <a href="#" onclick="return selectAuthenticationProvider('{AUTHPROVIDERS->id}')">
              <img src="{URL->BASE}/mods/social_authentication/providers/{AUTHPROVIDERS->id}/button.png"/>
            </a>
          </div>
        {/IF}
      {/LOOP AUTHPROVIDERS}

      <div id="social_authentication_authform">
        <table><tr><td valign="middle">
          <span class="authid_prompt"></span>
          <span class="authid_prefix"></span>
          <input type="text" name="authid" size="30"
                 id="social_authentication_authid"/>
          <span class="authid_postfix"></span>
          <input type="button" class="styled_button" value="{LANG->LogIn}"
                 id="social_authentication_submit_button"
                 onclick="startAuthentication()"
                 disabled="disabled"/>
          <input type="button" class="styled_button" value="{LANG->mod_social_authentication->ChooseAnotherProvider}"
                 onclick="return unselectAuthenticationProvider()"/>
        </td></tr></table>
      </div>

      <div id="social_authentication_progress">
        <table><tr><td valign="middle">
          <img src="{URL->BASE}/mods/social_authentication/images/ajax-loader.gif"/>
        </td><td valign="middle" style="padding-left: 10px">
          {LANG->mod_social_authentication->RedirectTitle}
        </td></tr></table>
      </div>
    </div>

  </div>
</form>

<script type="text/javascript">
//<![CDATA[

$sa_idselector = $PJ('#idselector');
$sa_provider   = $PJ('#social_authentication_provider');
$sa_authform   = $PJ('#social_authentication_authform');
$sa_authid     = $PJ('#social_authentication_authid');
$sa_button     = $PJ('#social_authentication_submit_button');
$sa_error      = $PJ('#social_authentication_error');
$sa_progress   = $PJ('#social_authentication_progress');

// Show the social authentication form. It is enabled by this code
// so the form won't be shown in browsers that have javascript disabled.
$sa_idselector.show();

// After loading the window, handle layouting the form.
$PJ(window).load(layoutSocialAuthenticationForm);

// Setup form submit event.
$sa_idselector.submit(submitAuthenticationForm);

// Setup input events for the authid form field.
$sa_authid.keyup(handleAuthidInput);
$sa_authid.bind('autocomplete click paste input change', handleAuthidInput);
$sa_authid.focus(startAuthidChangeLoop);
$sa_authid.blur(stopAuthidChangeLoop);

var social_authentication_prompts = {
  OpenID     : '{LANG->mod_social_authentication->OpenID}',
  UserName   : '{LANG->mod_social_authentication->UserName}',
  ScreenName : '{LANG->mod_social_authentication->ScreenName}',
  BlogName   : '{LANG->mod_social_authentication->BlogName}'
};

var social_authentication_config = {
  {LOOP AUTHPROVIDERS}
    {IF AUTHPROVIDERS->active}
      {VAR P AUTHPROVIDERS}
      '{P->id}': {
        protocol : '{P->provider->protocol}',
        type     : '{P->gui->type}',
        {IF NOT P->gui->type "redirect"}
        'prompt' : '{P->gui->prompt}',
        {/IF}
        url      : '{P->provider->url}'
      },
    {/IF}
  {/LOOP AUTHPROVIDERS}

  {! a dummy to make the javascript code valid }
  dummy: null
};

// Initialize the form when an existing state is available and the
// previously selected provider is not of the "redirect" type
// (otherwise opening the login page would instantly do a redirect.)
{IF AUTHENTICATION_PROVIDER}
var p = social_authentication_config['{AUTHENTICATION_PROVIDER}'];
if (p) {
    selectAuthenticationProvider('{AUTHENTICATION_PROVIDER}', false);
}
{/IF}
{IF AUTHENTICATION_AUTHID}
$sa_authid.val('{AUTHENTICATION_AUTHID}');
{/IF}

/**
 * Some layouting to keep the login form at the same height when
 * it is being collapsed. This prevents some scrollbar toggling
 * and page jumps.
 */
function layoutSocialAuthenticationForm()
{
    $sa_idselector.css({
        'height': $PJ('.social_authentication_login').outerHeight() + 'px'
    });
}

/**
 * Switch (back) to the authentication provider selection interface.
 */
function unselectAuthenticationProvider()
{
    $sa_error.hide();
    $sa_authform.hide();
    $sa_provider.val('');

    $PJ('.social_authentication_provider').show();

    layoutSocialAuthenticationForm();

    $PJ.cookie('sa_provider', '', { expires: 500, path: '/' });

    return false;
}

/**
 * Switch to the authentication interface for a selected authentication
 * provider.
 *
 * @param string id
 *   The id of the authentication provider.
 * @param boolean allow_redirect
 *   When false, then a provider of type "redirect" will not immediately
 *   redirect, but it will show the log in and cancel buttons instead.
 */
function selectAuthenticationProvider(id, allow_redirect)
{
    var config = social_authentication_config[id];

    // Just in case.
    if (!config) {
        return unselectAuthenticationProvider();
    }

    $PJ.cookie('sa_provider', id, { expires: 500, path: '/' });

    // Set the prompt for the authentication provider login.
    var prompt = social_authentication_prompts[config['prompt']];
    if (prompt === undefined) {
        prompt = '';
    } else {
        prompt = '{LANG->mod_social_authentication->EnterYourPre} ' +
                 prompt +
                 ' {LANG->mod_social_authentication->EnterYourPost}' +
                 ': ';
    }
    $sa_authform.find('.authid_prompt').html(prompt);

    $sa_provider.val(id);

    // Hide all providers, except the selected one.
    $PJ('.social_authentication_provider')
        .not('.provider_'+id)
        .hide();

    if (config['type'] === 'redirect') {
        $sa_authid.hide();
        if (allow_redirect !== false) {
            $sa_authform.hide();
            startAuthentication();
        } else {
            $sa_authform.show();
            $sa_button.attr('disabled', false);
        }
    } else {
        $sa_authid.show();
        $sa_authform.show()
        $sa_authform.find('input[type=text]').focus();
    }

    return false;
}

/**
 * Form submit handler. Only submit the form if an authid is filled in.
 *
 * @return boolean false
 *   so the standard form submit will be cancelled.
 */
function submitAuthenticationForm()
{
    var val = $sa_authid.val().trim();
    if (val !== '') {
        startAuthentication();
    }

    return false;
}

/**
 * Start the authentication process.
 *
 * This will call the discovery step of the authentication process.
 * An Ajax call will be done to the API for handling the discovery
 * step. The API will return redirect code that the GUI must execute
 * to do the authentication request to the authentication provider site.
 *
 * Also keep track of the login form status in a cookie. We can use this
 * cookie to restore the login form status on page reload.
 */
function startAuthentication()
{
    $sa_error.hide();
    $sa_authform.hide();
    $sa_progress.show();

    $PJ.cookie('sa_provider', $sa_provider.val(), { expires: 500, path: '/' });
    $PJ.cookie('sa_authid',   $sa_authid.val(), { expires: 500, path: '/' });

    Phorum.Ajax.call({
        call     : 'social_authentication',
        action   : 'initialize',
        provider : $sa_provider.val(),
        authid   : $sa_authid.val().trim(),
        redir    : $PJ('#idselector input[name=redir]').val(),

        // On success, the API will return HTML + JavaScript code to execute
        // for handling the redirect step of the authentication process.
        onSuccess : function (result) {
          $PJ('#phorum').append(result);
        },

        // Handle errors.
        onFailure : function (error) {
          $sa_error.html(error);
          $sa_error.show();
          $sa_progress.hide();
          $sa_authform.show();
          if ($sa_authid.is(':visible')) {
            $sa_authid.focus();
          }
        }
    });

    return false;
}

// The functions below are used for doing input monitoring on the
// authid field. A timed loop is setup to be able to detect changes
// on input events for which no browser DOM event is available
// (e.g. when selecting an autofill option for the field from a drop down
// list by clicking on it.)

var authid_change_timer = null;

function handleAuthidInput()
{
    // Settimeout will give the browser a chance to fill the value of
    // the authid field when data is pasted.
    setTimeout(function () {
        var val = $sa_authid.val().trim();
        if (val === '') {
            $sa_button.attr('disabled', true);
        } else {
            $sa_button.attr('disabled', false);
        }
    }, 1);
}

function startAuthidChangeLoop()
{
    if (authid_change_timer) return;

    authid_change_timer = setTimeout(function () {
        handleAuthidInput();
        authid_change_timer = null;
        startAuthidChangeLoop();
    }, 250);
}

function stopAuthidChangeLoop()
{
    if (authid_change_timer) {
        clearTimeout(authid_change_timer);
        authid_change_timer = null;
    }
}

// ]]>
</script>
