function ValAlt(mask)
{
	//alert(mask);
	altezza = window.screen.height;
	
	if (mask == "corpo")
	{
		jam = 35000;
		return Math.round((altezza+jam)/altezza);
	}
	else if (mask == "corpo_sx")
	{
		jam = 19000;
		return Math.round((altezza+jam)/altezza);
	}
	else if (mask == "corpo_dx")
	{
		jam = 18000;
		return Math.round((altezza+jam)/altezza);
	}
	else if (mask == "login")
	{
		jam = 13000;
		return Math.round((altezza+jam)/altezza);
	}
	else if (mask == "privacy")
	{
		jam = 19000;
		return Math.round((altezza+jam)/altezza);
	}
	else if (mask == "logout")
	{
		jam = 14000;
		return Math.round((altezza+jam)/altezza);
	}
	else if (mask == "register")
	{
		jam = 35000;
		return Math.round((altezza+jam)/altezza);
	}
	else if (mask == "sendpwd")
	{
		jam = 14000;
		return Math.round((altezza+jam)/altezza);
	}
	else if (mask == "errato")
	{
		jam = 12000;
		return Math.round((altezza+jam)/altezza);
	}
	else if (mask == "prossimo")
	{
		jam = 16000;
		return Math.round((altezza+jam)/altezza);
	}
	else if (mask == "statistiche_squadra")
	{
		jam = 21000;
		return Math.round((altezza+jam)/altezza);
	}
	else if (mask == "statistiche_frame")
	{
		jam = 40000;
		return Math.round((altezza+jam)/altezza);
	}
	else if (mask == "statistiche_frame_staff")
	{
		jam = 32000;
		return Math.round((altezza+jam)/altezza);
	}
	else if (mask == "statistiche_giocatori")
	{
		jam = 30000;
		return Math.round((altezza+jam)/altezza);
	}
	else if (mask == "statistiche_staff")
	{
		jam = 6000;
		return Math.round((altezza+jam)/altezza);
	}
	else if (mask == "stampa")
	{
		jam = 34000;
		return Math.round((altezza+jam)/altezza);	
	}
	else if (mask == "profilo")
	{
		jam = 31000;
		return Math.round((altezza+jam)/altezza);
	}
	else if (mask == "staff")
	{
		jam = 23000;
		return Math.round((altezza+jam)/altezza);
	}
	else if (mask == "bonus")
	{
		jam = 11000;
		return Math.round((altezza+jam)/altezza);
	}
	else if (mask == "motiva")
	{
		jam = 34000;
		return Math.round((altezza+jam)/altezza);
	}
	else if (mask == "teamlist")
	{
		jam = 52500;
		return Math.round((altezza+jam)/altezza);
	}
	else if (mask == "player_posizione")
	{
		jam = 35000;
		return Math.round((altezza+jam)/altezza);
	}
	else if (mask == "player")
	{
		jam = 9000;
		return Math.round((altezza+jam)/altezza);
	}
	else if (mask == "player_abilita")
	{
		jam = 33000;
		return Math.round((altezza+jam)/altezza);
	}
	else if (mask == "classifica")
	{
		jam = 24000;
		return Math.round((altezza+jam)/altezza);
	}
	else if (mask == "calendario")
	{
		jam = 33000;
		return Math.round((altezza+jam)/altezza);
	}

}


