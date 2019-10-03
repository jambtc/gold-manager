function ValAlt(mask)
{
	//alert(mask);
	altezza = window.screen.height;
	
	if (mask == "corpo")
	{
		if (altezza < 801)
		{
			return 40;
		}
		else if (altezza >800 && altezza <1025)
		{
			return 32;
		}
		else 
		{
			return 30;
		}
	}
	else if (mask == "login")
	{
		if (altezza < 801)
		{
			return 50;
		}
		else
		{
			return 15;
		}
	}
	else if (mask == "privacy")
	{
		if (altezza < 801)
		{
			return 26;
		}
		else if (altezza >800 && altezza <1025)
		{
			return 20;
		}
		else
		{
			return 18;
		}
	}
	else if (mask == "register")
	{
		if (altezza < 801)
		{
			return 31;
		}
		else if (altezza >800 && altezza <1025)
		{
			return 25;
		}
		else
		{
			return 38;
		}
	}
	else if (mask == "sendpwd")
	{
		if (altezza < 801)
		{
			return 15;
		}
		else if (altezza >800 && altezza <1025)
		{
			return 14;
		}
		else
		{
			return 13;
		}
	}
	else if (mask == "errato")
	{
		if (altezza < 801)
		{
			return 16;
		}
		else if (altezza >800 && altezza <1025)
		{
			return 11;
		}
		else
		{
			return 13;
		}
	}
	else if (mask == "prossimo")
	{
		if (altezza < 801)
		{
			return 18;
		}
		else if (altezza >800 && altezza <1025)
		{
			return 16;
		}
		else
		{
			return 14;
		}
	}
	else if (mask == "statistiche_squadra")
	{
		if (altezza < 801)
		{
			return 28;
		}
		else if (altezza >800 && altezza <1025)
		{
			return 22;
		}
		else
		{
			return 14;
		}
	}
	else if (mask == "statistiche_frame")
	{
		if (altezza < 801)
		{
			return 51;
		}
		else if (altezza >800 && altezza <1025)
		{
			return 42;
		}
		else
		{
			return 40;
		}
	}else if (mask == "statistiche_frame_staff")
	{
		if (altezza < 801)
		{
			return 30;
		}
		else if (altezza >800 && altezza <1025)
		{
			return 30;
		}
		else
		{
			return 30;
		}
	}
	else if (mask == "statistiche_giocatori")
	{
		if (altezza < 801)
		{
			return 40;
		}
		else if (altezza >800 && altezza <1025)
		{
			return 30;
		}
		else
		{
			return 20;
		}
	}
	else if (mask == "statistiche_staff")
	{
		if (altezza < 801)
		{
			return 9;
		}
		else if (altezza >800 && altezza <1025)
		{
			return 7;
		}
		else
		{
			return 5;
		}
	}
	
	
	
}


