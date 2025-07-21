import React from 'react';
import { Link } from 'react-router-dom';
import {
  Box,
  Container,
  Typography,
  Grid,
  Link as MuiLink,
  Divider,
} from '@mui/material';
import {
  Security as SecurityIcon,
  GitHub as GitHubIcon,
  Email as EmailIcon,
} from '@mui/icons-material';

const Footer: React.FC = () => {
  return (
    <Box
      component="footer"
      sx={{
        bgcolor: 'grey.100',
        py: 6,
        mt: 'auto',
      }}
    >
      <Container maxWidth="lg">
        <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'repeat(3, 1fr)' }, gap: 4 }}>
          <Box>
            <Box display="flex" alignItems="center" mb={2}>
              <SecurityIcon sx={{ mr: 1, color: 'primary.main' }} />
              <Typography variant="h6" fontWeight={600}>
                BF Secret File Downloader
              </Typography>
            </Box>
            <Typography variant="body2" color="text.secondary" paragraph>
              WordPressプラグインで最も安全なファイル管理を実現します。
              BASIC認証とセキュリティ機能で、大切なファイルを保護します。
            </Typography>
          </Box>

          <Box>
            <Typography variant="h6" gutterBottom>
              製品情報
            </Typography>
            <Box component="ul" sx={{ listStyle: 'none', p: 0, m: 0 }}>
              <Box component="li" sx={{ mb: 1 }}>
                <MuiLink component={Link} to="/features" color="inherit" sx={{ textDecoration: 'none' }}>
                  クイックスタート
                </MuiLink>
              </Box>
              <Box component="li" sx={{ mb: 1 }}>
                <MuiLink component={Link} to="/docs" color="inherit" sx={{ textDecoration: 'none' }}>
                  ドキュメント
                </MuiLink>
              </Box>
              <Box component="li" sx={{ mb: 1 }}>
                <MuiLink href="https://github.com/your-repo/bf-secret-file-downloader" color="inherit" sx={{ textDecoration: 'none' }}>
                  GitHub
                </MuiLink>
              </Box>
            </Box>
          </Box>

          <Box>
            <Typography variant="h6" gutterBottom>
              お問い合わせ
            </Typography>
            <Box display="flex" alignItems="center" mb={1}>
              <EmailIcon sx={{ mr: 1, fontSize: 'small' }} />
              <Typography variant="body2">
                support@example.com
              </Typography>
            </Box>
            <Box display="flex" alignItems="center" mb={1}>
              <GitHubIcon sx={{ mr: 1, fontSize: 'small' }} />
              <Typography variant="body2">
                GitHub Issues
              </Typography>
            </Box>
          </Box>
        </Box>

        <Divider sx={{ my: 3 }} />

        <Box display="flex" justifyContent="space-between" alignItems="center" flexWrap="wrap">
          <Typography variant="body2" color="text.secondary">
            © 2024 BF Secret File Downloader. All rights reserved.
          </Typography>
          <Box display="flex" gap={2}>
            <Typography variant="body2" color="text.secondary">
              <MuiLink href="#" color="inherit" sx={{ textDecoration: 'none' }}>
                プライバシーポリシー
              </MuiLink>
            </Typography>
            <Typography variant="body2" color="text.secondary">
              <MuiLink href="#" color="inherit" sx={{ textDecoration: 'none' }}>
                利用規約
              </MuiLink>
            </Typography>
          </Box>
        </Box>
      </Container>
    </Box>
  );
};

export default Footer;