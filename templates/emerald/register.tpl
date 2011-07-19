{IF SOCIAL_AUTHENTICATION_ERROR}
  <div class="attention">{SOCIAL_AUTHENTICATION_ERROR}</div>
{/IF}
{IF OKMSG}<div class="information">{OKMSG}</div>{/IF}

<div class="generic">
  <p>{LANG->mod_social_authentication->RegisterExplanation}</p>
  <form action="{URL->ACTION}" method="post" style="display: inline;">
    {POST_VARS}
    <input type="hidden" name="_sas" value="register" />
    <input type="hidden" name="_sap"
           value="{AUTHENTICATION_PROVIDER}" />
    <input type="hidden" name="step" value="{STEP}" />
    <table class="form-table" style="width:auto">
      <tr>
        <td>*&nbsp;</td>
        <td nowrap="nowrap">{LANG->Username}&nbsp;</td>
        <td><input type="text" name="nickname" size="30" value="{REGISTER->nickname}" /></td>
      </tr>
      <tr>
        <td></td>
        <td nowrap="nowrap">{LANG->RealName}&nbsp;</td>
        <td><input type="text" name="fullname" size="30" value="{REGISTER->fullname}" /></td>
      </tr>
      <tr>
        <td>*&nbsp;</td>
        <td nowrap="nowrap">{LANG->Email}&nbsp;</td>
        <td><input type="text" name="email" size="30" value="{REGISTER->email}" /></td>
      </tr>
    </table>

    <div style="margin-top: 15px;">
      <small>*{LANG->Required}</small>
    </div>

    <div style="margin-top: 15px;">
      <input type="submit" value=" {LANG->Register} " />
    </div>

  </form>
</div>
