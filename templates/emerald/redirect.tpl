<div style="display:none">

  {IF REDIRECT->METHOD "post"}
    {REDIRECT->FORM}
  {/IF}

  <script type="text/javascript">
  //<![CDATA[
    {IF REDIRECT->METHOD "post"}
      $PJ('#{REDIRECT->FORM_ID}').submit();
    {ELSE}
      document.location.href = '{REDIRECT->URL}';
    {/IF}
  // ]]>
  </script>

</div>
