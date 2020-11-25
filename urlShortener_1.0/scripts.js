jQuery(document).ready(function($) 
{
  var long_url_field=$('input#url_long'); 
  long_url_field.val('');
  var long_url_value='';
  var long_url_error_field=$('output#url_long_error');
  var length_field=$('input#url_length');
  var generate_button=$('#generate_short_url');
  var short_url_field=$('output#url_short');
  // var delete_button=$('#generate_short_url');

  generate_button.on('click', function(e) {
    e.preventDefault();
    long_url_value=long_url_field.val();
    long_url_error_field.val('');

    var option = document.querySelector('input[name="url_shortener_string_option"]:checked'); 
    if (option)
      option=option.value; 
    var length = length_field.val();

    if (isValidUrl(long_url_value) && option && length>0 && length<=10)
    {
      var requestOptions = {
        method: 'POST',
        redirect: 'follow'
      };
      generate_button.prop('disabled', true);
      var url=document.location.origin+'/wp-json/urlShortener/v1/post-call?url_long='
              +long_url_value+'&length='+length+'&option='+option;

        fetch(url, requestOptions)
        .then(response => response.text())
        // .then(result => console.log(result))
        .then(result => {
                          if (result)
                            short_url_field.val(result);
                          else long_url_error_field.val('URL has been used already.');
                        })
        .catch(error => console.log('error', error));  
    }
    else 
    {
      if (!option)
        long_url_error_field.val('Choose an option.');
        else if(length<1 || length>10)
        long_url_error_field.val('Length is not correct.');
        else long_url_error_field.val('URL is not the correct format.');
    }
  });

  long_url_field.on('input', function() 
  {
    // console.log('field change');
    generate_button.prop('disabled', false);
  });

});

function delete_url(url_short){
  var requestOptions = {
    method: 'DELETE',
    redirect: 'follow'
  };
  result = confirm("Are you sure you want to delete this URL?");
  if (result) 
  {
    var url=document.location.origin+'/wp-json/urlShortener/v1/delete?url_short='+url_short;

    fetch(url, requestOptions)
      .then(response => response.text())
      .then(result => console.log(result))
      .catch(error => console.log('error', error));
  }
  
}

function edit_url(scheme, host, short_path_old, span_id, button_id, error_id, visit_id, e)
{
  scheme_host=scheme+'://'+host+'/';
  span_id='#'+span_id;
  button_id='#'+button_id;
  error_id='#'+error_id;
  visit_id='#'+visit_id;
  url_short_old=scheme_host+short_path_old;

  jQuery(document).ready(function($) 
  {
      e.preventDefault();
      if($(button_id).val()=='Edit')
      {
        $(button_id).val('Save');
        $(span_id).attr('contenteditable',true);
      }
      else
      {
        $(error_id).val('');
        var requestOptions = {
          method: 'POST',
          redirect: 'follow'
        };
 
        url_short_new=scheme_host+$(span_id).html();
        
        var url=document.location.origin+'/wp-json/urlShortener/v1/edit-url?url_short_old='
                +url_short_old+'&url_short_new='+url_short_new;
  
          fetch(url, requestOptions)
          .then(response => response.text())
          .then(result => {
                            // console.log(result);
                            if (result == 0)
                            {
                              $(error_id).val('value cannot be empty.');
                            }
                            else if (result == 1)
                            {
                              $(error_id).val('URL already in use.');
                            }
                            else
                            {
                              $(button_id).val('Edit');
                              $(span_id).attr('contenteditable',false);
                              //change Visit href
                              $(visit_id).attr('href',url_short_new);
                              // console.log($(visit_id).href());
                              $(visit_id).href=url_short_new;
                            } 
                          })
          .catch(error => console.log('error', error));  
      } 

  });
  
}

function isValidUrl(string) 
{
  try 
  {
    new URL(string);
  } 
  catch (_) 
  {
    return false;  
  }
  return true;
}