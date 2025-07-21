import React from 'react';
import {
  Box,
  Typography,
  Paper,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Button,
  IconButton,
  Chip,
} from '@mui/material';
import {
  Download as DownloadIcon,
  Delete as DeleteIcon,
  Edit as EditIcon,
} from '@mui/icons-material';

const FileManagement: React.FC = () => {
  const files = [
    {
      id: 1,
      name: 'document.pdf',
      size: '2.5 MB',
      type: 'PDF',
      status: 'active',
      downloads: 45,
      lastModified: '2024-01-15',
    },
    {
      id: 2,
      name: 'report.xlsx',
      size: '1.8 MB',
      type: 'Excel',
      status: 'active',
      downloads: 23,
      lastModified: '2024-01-14',
    },
    {
      id: 3,
      name: 'presentation.pptx',
      size: '5.2 MB',
      type: 'PowerPoint',
      status: 'inactive',
      downloads: 12,
      lastModified: '2024-01-13',
    },
  ];

  return (
    <Box>
      <Box display="flex" justifyContent="space-between" alignItems="center" mb={3}>
        <Typography variant="h4">
          ファイル管理
        </Typography>
        <Button variant="contained" color="primary">
          ファイルを追加
        </Button>
      </Box>

      <Paper>
        <TableContainer>
          <Table>
            <TableHead>
              <TableRow>
                <TableCell>ファイル名</TableCell>
                <TableCell>サイズ</TableCell>
                <TableCell>タイプ</TableCell>
                <TableCell>ステータス</TableCell>
                <TableCell>ダウンロード数</TableCell>
                <TableCell>最終更新</TableCell>
                <TableCell>アクション</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {files.map((file) => (
                <TableRow key={file.id}>
                  <TableCell>{file.name}</TableCell>
                  <TableCell>{file.size}</TableCell>
                  <TableCell>{file.type}</TableCell>
                  <TableCell>
                    <Chip
                      label={file.status === 'active' ? '有効' : '無効'}
                      color={file.status === 'active' ? 'success' : 'default'}
                      size="small"
                    />
                  </TableCell>
                  <TableCell>{file.downloads}</TableCell>
                  <TableCell>{file.lastModified}</TableCell>
                  <TableCell>
                    <IconButton size="small" color="primary">
                      <DownloadIcon />
                    </IconButton>
                    <IconButton size="small" color="secondary">
                      <EditIcon />
                    </IconButton>
                    <IconButton size="small" color="error">
                      <DeleteIcon />
                    </IconButton>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </TableContainer>
      </Paper>
    </Box>
  );
};

export default FileManagement;