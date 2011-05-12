var viewportwidth;
var viewportheight;
var fadingDirection; //0 up, 1 down

function load()
{
	fadingDirection = 0;
	resize();
	//fadeBackgroundPicture();
	publishPosterPicture(0);
	publishPicture(0);
}			

function resize()
{
	setViewPortSizes();
	document.body.style.fontSize = viewportwidth/76+'px';	
}
function setViewPortSizes()
{
	// the more standards compliant browsers (mozilla/netscape/opera/IE7) use window.innerWidth and window.innerHeight
	 if (typeof window.innerWidth != 'undefined')
	 {
	      viewportwidth = window.innerWidth;
	      viewportheight = window.innerHeight;
	 }
	// IE6 in standards compliant mode (i.e. with a valid doctype as the first line in the document)
	 else if (typeof document.documentElement != 'undefined'
	     && typeof document.documentElement.clientWidth !=
	     'undefined' && document.documentElement.clientWidth != 0)
	 {
	       viewportwidth = document.documentElement.clientWidth;
	       viewportheight = document.documentElement.clientHeight;
	 }
	 // older versions of IE
	 else
	 {
	       viewportwidth = document.getElementsByTagName('body')[0].clientWidth;
	       viewportheight = document.getElementsByTagName('body')[0].clientHeight;
	 }
}

/*
function fadeBackgroundPicture()
{
	var currentOpacity = document.body.style.opacity;
	alert('Opacity '+document.body.style.opacity);
	if(fadingDirection == 0) //UP
	{
		currentOpacity += 0.01;
		if(currentOpacity >= 1.0)
			fadingDirection = 1;
	}
	else
	{
		currentOpacity -= 0.01;
		if(currentOpacity <= 0.0)
			fadingDirection = 0;
	}
	document.body.style.opacity = currentOpacity.toString();
	setTimeout('fadeBackgroundPicture()',25);
}
*/

function publishPicture(i)
{
	if(fanartArray != null)
	{
		document.body.style.backgroundImage = 'url(\"'+fanartArray[i]+'\")';
		document.body.style.backgroundAttachment = 'fixed';
		
		i++;
		if( i > (fanartArray.length - 1) ) { i = 0; }
		
		preload_image_object = new Image();
		preload_image_object.src = fanartArray[i];
		
		setTimeout('publishPicture('+i+')',5000);
	}
}

function publishPosterPicture(i)
{
	if(posterArray != null)
	{
		changePosterImage(posterArray[i],posterLinkArray[i]);
		i++;
		if( i > (posterArray.length - 1) ) { i = 0; }
		
		preload_image_object = new Image();
		preload_image_object.src = posterArray[i];
		
		setTimeout('publishPosterPicture('+i+')',5000);
	}
}



function changePosterImage(path,href)
{
	var image = document.getElementById('cover');
	image.src = path;
	var link = document.getElementById('coverhref');
	link.href = href;
}

function displayImage(path)
{
	var image = document.getElementById('pictureDisplayerImage');
	var displayer = document.getElementById('pictureDisplayer');
	
	var new_image = new Image();
	new_image.src = path;
    
	
	var ratio = 1.0;
	if(ratio != 1.0)
		ratio = 1.0;
	
	if(new_image.height > viewportheight * 0.8)
	{
		ratio = (viewportheight * 0.8) / new_image.height;
		new_image.height = viewportheight * 0.8;
	}
	if(ratio != 1.0)
		new_image.width = new_image.width * ratio; 
	
	if(new_image.width > viewportwidth * 0.8)
	{
		ratio = (viewportwidth * 0.8) / new_image.width;
		new_image.width = viewportwidth * 0.8;
		new_image.height = new_image.height * ratio; 
	}
	image.src = new_image.src;
	image.width = new_image.width;
	image.height = new_image.height;

	
	
	image.style.left = (viewportwidth / 2 - image.width / 2)+'px';
	image.style.top = (viewportheight / 2 - image.height / 2)+'px';
	
	image.style.display = 'block';
	displayer.style.display = 'block';
}

function hidePictureDisplayer()
{
	document.getElementById('pictureDisplayer').style.display = 'none';
	document.getElementById('pictureDisplayerImage').style.display = 'none';
}

function hideCornerTable()
{
	document.getElementById('cornerTable').style.display = 'none';
	document.getElementById('showCornerTableButton').style.display = 'block';
}

function displayCornerTable()
{
	document.getElementById('showCornerTableButton').style.display = 'none';
	document.getElementById('cornerTable').style.display = 'block';
}

function highlightCell(cellId)
{
	document.getElementById(cellId).style.backgroundColor = '#666666';
}

function removeHighlightForCell(cellId)
{
	document.getElementById(cellId).style.backgroundColor = '#000000';
}
