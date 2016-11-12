var main = function() {
  $('.btn-back').hide();
  $('.btn-back').css("background-color",$('.active-slide').css("background-color"));
  $('.btn-learn').click(function() {
    $('.form-group').fadeOut(400);
    $('.header1').fadeOut(400);
    $('.sign-in-opt').fadeOut(400);
    $('.join-with').fadeOut(400);
    $('.title1 p').fadeOut(400);
    $('.title1').delay(500).animate({
      top:"-1.5%",
      background:"normal"
    },500);
    $('.title1 h1').delay(500).animate({
      fontSize:"40px",
      marginBottom:"0px"
    },500);
    $('.btn-back').delay(500).fadeIn(500);
    $('.jumbotron').delay(500).animate({
      top:"93%"
    },500);
    $('.title1').delay(500).addClass('btn');
  });
  $('.title1').click(function() {
    $('.title1').removeClass('btn');
    $('.title1').animate({
      top:"10%"
    },500);
    $('.title1 h1').animate({
      fontSize:"80px",
      marginBottom:"10px"
    },500);
    $('.btn-back').fadeOut(500);
    $('.jumbotron').animate({
      top:"0%"
    },500);
    $('.form-group').delay(500).fadeIn(400);
    $('.header1').delay(500).fadeIn(400);
    $('.sign-in-opt').delay(500).fadeIn(400);
    $('.join-with').delay(500).fadeIn(400);
    $('.title1 p').delay(500).fadeIn(400);
  });
  $('.btn-sign-in').click(function() {
    $('.header1').animate({
      top: "-12%"
    }, 200);
    $('.sign-in-opt').delay(200).animate({
      right: "0%"
    }, 200);
  });

  $('.btn-close').click(function() {
    $('.sign-in-opt').animate({
      right: "-35%"
    }, 200);
    $('.header1').delay(200).animate({
      top: "0.2%"
    }, 200);
  });

  $('#dotNav li').click(function(){
    var currentSlide = $('.active-slide');
    var currentDot=$('.active');
    var id = $(this).find('a').attr("href");    
    var ele = $(id);
    currentDot.removeClass("active");
    $(this).addClass("active");
    currentSlide.fadeOut(600).removeClass('active-slide');
    ele.fadeIn(600).addClass("active-slide");
    $('.btn-back').css("background-color",ele.css("background-color"));
  });

  $(document).keydown(function(event) {
    if(event.keyCode === 38 || event.keyCode === 39) {
          var currentSlide = $('.active-slide');
          var currentDot=$('.active');
          var nextSlide = currentSlide.next();  
          var nextDot = currentDot.next();  
          if(nextSlide.length === 0) {
            nextSlide = $('.slide').first();
            nextDot = $('.dott').first();
          }
          currentDot.removeClass("active");
          nextDot.addClass("active");
          currentSlide.fadeOut(600).removeClass('active-slide');
          nextSlide.fadeIn(600).addClass("active-slide");
          $('.btn-back').css("background-color",nextSlide.css("background-color"));
    }
    if(event.keyCode === 37 || event.keyCode === 40) {
          var currentSlide = $('.active-slide');
          var currentDot=$('.active');
          var prevSlide = currentSlide.prev();    
          var prevDot = currentDot.prev();
          if(prevSlide.length === 0) {
            prevSlide = $('.slide').last();
            prevDot = $('.dott').last();
          }
          currentDot.removeClass("active");
          prevDot.addClass("active");
          currentSlide.fadeOut(600).removeClass('active-slide');
          prevSlide.fadeIn(600).addClass("active-slide");
          $('.btn-back').css("background-color",prevSlide.css("background-color"));
    }
  });

  var lastScrollTop = 0;
  $(window).bind('mousewheel', function(event) {
    if (event.originalEvent.wheelDelta >= 0) {
          var currentSlide = $('.active-slide');
          var currentDot=$('.active');
          var prevSlide = currentSlide.prev();    
          var prevDot = currentDot.prev();
          if(prevSlide.length === 0) {
            prevSlide = $('.slide').last();
            prevDot = $('.dott').last();
          }
          currentDot.removeClass("active");
          prevDot.addClass("active");
          currentSlide.fadeOut(600).removeClass('active-slide');
          prevSlide.fadeIn(600).addClass("active-slide");
          $('.btn-back').css("background-color",prevSlide.css("background-color")); 
    }
    else {
          var currentSlide = $('.active-slide');
          var currentDot=$('.active');
          var nextSlide = currentSlide.next();  
          var nextDot = currentDot.next();  
          if(nextSlide.length === 0) {
            nextSlide = $('.slide').first();
            nextDot = $('.dott').first();
          }
          currentDot.removeClass("active");
          nextDot.addClass("active");
          currentSlide.fadeOut(600).removeClass('active-slide');
          nextSlide.fadeIn(600).addClass("active-slide");
          $('.btn-back').css("background-color",nextSlide.css("background-color"));
    }
  });
  

};

$(document).ready(main);