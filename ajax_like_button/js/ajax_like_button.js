
jQuery(document).ready(function($){
     $(".fd_like").click(function(){
         var current=this;
         
         console.log(current.id);
        $.post(fd_like_button_ajax_object.ajax_url,{
            _ajax_nonce: fd_like_button_ajax_object.nonce,
            action: "fd_like_button_action",            //action
             fd_my_data1: current.style.color , 
             fd_my_data2: current.id


        } ,
            function (data) {
                if(data=="green")
                current.style.color="green";
                else
                current.style.color="black";
                
                
            }
            
        );
     })
   
     $(".fd_like, .fd_visitor_like").mouseover(function(){
         var a=this;
         a.style.cursor = "pointer";
         
     })

})