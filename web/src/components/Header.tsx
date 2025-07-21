import React, { useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import {
  AppBar,
  Toolbar,
  Typography,
  Button,
  Box,
  Container,
  IconButton,
  Drawer,
  List,
  ListItem,
  ListItemText,
  useTheme,
  useMediaQuery,
} from '@mui/material';
import {
  Menu as MenuIcon,
  Security as SecurityIcon,
} from '@mui/icons-material';

const Header: React.FC = () => {
  const [mobileOpen, setMobileOpen] = useState(false);
  const location = useLocation();
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('md'));

  const menuItems = [
    { text: 'ホーム', path: '/' },
    { text: 'クイックスタート', path: '/features' },
    { text: 'ドキュメント', path: '/docs' },
    { text: 'お問い合わせ', path: '/contact' },
  ];

  const handleDrawerToggle = () => {
    setMobileOpen(!mobileOpen);
  };

  const drawer = (
    <List>
      {menuItems.map((item) => (
        <ListItem
          key={item.text}
          component={Link}
          to={item.path}
          onClick={handleDrawerToggle}
          sx={{
            backgroundColor: location.pathname === item.path ? 'action.selected' : 'transparent',
          }}
        >
          <ListItemText primary={item.text} />
        </ListItem>
      ))}
    </List>
  );

  return (
    <AppBar position="static" color="default" elevation={1} sx={{ backgroundColor: '#1976d2', color: 'white' }}>
      <Container maxWidth="lg">
        <Toolbar>
          <Box display="flex" alignItems="center" sx={{ flexGrow: 1 }}>
            <SecurityIcon sx={{ mr: 1, color: 'white' }} />
            <Typography variant="h6" component={Link} to="/" sx={{
              textDecoration: 'none',
              color: 'white',
              fontWeight: 600
            }}>
              BF Secret File Downloader
            </Typography>
          </Box>

          {isMobile ? (
            <IconButton
              color="inherit"
              aria-label="open drawer"
              edge="start"
              onClick={handleDrawerToggle}
              sx={{ color: 'white' }}
            >
              <MenuIcon />
            </IconButton>
          ) : (
            <Box sx={{ display: 'flex', gap: 2 }}>
              {menuItems.map((item) => (
                <Button
                  key={item.text}
                  component={Link}
                  to={item.path}
                  color="inherit"
                  sx={{
                    textTransform: 'none',
                    fontWeight: location.pathname === item.path ? 600 : 400,
                    color: 'white',
                    position: 'relative',
                    '&:hover': {
                      backgroundColor: 'rgba(255, 255, 255, 0.1)',
                    },
                    ...(location.pathname === item.path && {
                      '&::after': {
                        content: '""',
                        position: 'absolute',
                        bottom: 0,
                        left: 0,
                        right: 0,
                        height: '2px',
                        backgroundColor: 'white',
                        borderRadius: 0,
                      }
                    })
                  }}
                >
                  {item.text}
                </Button>
              ))}
            </Box>
          )}
        </Toolbar>
      </Container>

      <Drawer
        variant="temporary"
        anchor="right"
        open={mobileOpen}
        onClose={handleDrawerToggle}
        ModalProps={{
          keepMounted: true,
        }}
        sx={{
          display: { xs: 'block', md: 'none' },
          '& .MuiDrawer-paper': { boxSizing: 'border-box', width: 240 },
        }}
      >
        {drawer}
      </Drawer>
    </AppBar>
  );
};

export default Header;