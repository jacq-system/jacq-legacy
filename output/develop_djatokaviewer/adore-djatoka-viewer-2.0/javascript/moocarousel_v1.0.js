/*
Title 	: Simple Carousel with Paging Using Mootools
Author 	: Nikhil Kunder (nik1409@gmail.com)
Date 	: 2008/09/12
Version : 1.0
    moocarousel_v1.0.js  is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
 	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 	Lesser General Public License for more details.
*/

var MooCarousel = new Class({
		wrapper:'',
		items:'',
		moveleft:'',
		moveright:'',
		slides:2,
		offset:350,
		currentslide:1,
		pos:0,
		ispaged:false,
		//aa:{},
		initialize: function(wrapper,items,moveleft,moveright, ns,sss,ispaged){
			//this.setOptions(options);
			this.wrapper = $(wrapper);
			this.items = $(items);
			this.moveleft = $(moveleft);
			this.moveright = $(moveright);
			this.slides = ns;
			this.offset = sss;
			this.ispaged = ispaged;
			this.parent = this.wrapper.getParent();
			this.scroll = new Fx.Scroll(this.wrapper, {offset:{'x':0, 'y':0} });
			this.dir = "right"; // direction of paging
			var that = this;
			
			if(this.ispaged){
				this.carousel_paging = new Element('div').addClass('carousel_paging');
				this.carousel_paging.id= this.wrapper.id + "_p";
				//alert(parseInt(this.slides));
				//	for (  i = parseInt(this.slides); i>0 ; i--){
				for (  i = 1;  i <= parseInt(this.slides) ; i++){
					var aa = new Element('a').addClass('page');
					if(i==1) aa.className= "current";
					aa.href="javascript:void(0);";
					aa.addEvent('click', this.page.bind(this, [i, aa, this.carousel_paging]));
					aa.innerHTML = i;
					aa.injectInside(this.carousel_paging);
				}
				//alert(this.carousel_paging.innerHTML);
				this.carousel_paging.injectAfter(this.parent);
				var carousel_fix = new Element('div').addClass('clearfix').injectBefore(this.carousel_paging);
				var carousel_fix = new Element('div').addClass('clearfix').injectInside(this.carousel_paging);
			}
			this.moveleft.addEvent('click', this.camoveleft.bind(this));
			this.moveright.addEvent('click', this.camoveright.bind(this));
			
		},
		
		
		camoveleft: function(event){
			event = new Event(event).stop();
			if(this.currentslide == 1) return;
			//this.aa[this.currentslide].className= "paging_anchor current"
			this.currentslide--;	
			if(this.ispaged){																						
				this.setcss('left');
				this.dir = 'left';
			}
			this.pos += -(this.offset);
			this.scroll.start(this.pos);this.scroll.toLeft();
			
		},
		camoveright: function(event){
			event = new Event(event).stop();
			if(this.currentslide >= this.slides) return;
			this.currentslide++;
			if(this.ispaged){																						
				this.setcss('right');
				this.dir = 'right';
			}
			this.pos += this.offset;
			this.scroll.start(this.pos);this.scroll.toLeft();
			//this.resetcss();
			//this.aa[this.currentslide].className= "paging_anchor current"
		},
		
		page: function(pagenum,o, p){
			//event = new Event(event).stop();
			//alert("page -" + pagenum);
			var sss = ((pagenum-1)*this.offset) ;
			if(pagenum > this.slides) return;
			if(pagenum == 1) sss = 0;
			this.currentslide = pagenum
			this.pos = sss
			this.scroll.start(this.pos);this.scroll.toLeft();
			//o.className="current";
			this.resetcss(o, p);
			/*var pa = $$(".paging_anchor");
				pa.each(function(el,i){
				el.className="page";
			});*/
		},
		setcss:function(dir){
			var x = parseInt(this.currentslide)-1; 		
			if(x < 0 ) x = 0; if( x >9) x =9; 
			var o = this.carousel_paging.getElements('a')[x];
			this.resetcss(o,this.carousel_paging);
		},
		resetcss: function(o,p){
			var cpa = p.getElements('a');
			cpa.each(function(el,i){
				el.className="page";
			});
			o.className="current";
		}
		
	});
	MooCarousel.implement(new Events);
	MooCarousel.implement(new Options);