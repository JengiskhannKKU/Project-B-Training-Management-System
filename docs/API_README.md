# Training Management System - API Guide

> คู่มือการใช้งาน API สำหรับ Frontend Developers

**Version:** 1.0
**Last Updated:** 12 ธันวาคม 2025

---

## 📋 สารบัญ

- [เกี่ยวกับ API](#เกี่ยวกับ-api)
- [เริ่มต้นใช้งาน](#เริ่มต้นใช้งาน)
- [Authentication](#authentication)
- [ตัวอย่างการใช้งาน](#ตัวอย่างการใช้งาน)
- [Error Handling](#error-handling)
- [Best Practices](#best-practices)

---

## 🎯 เกี่ยวกับ API

### Base URL
```
Development: http://localhost:8000/api
Production: https://your-domain.com/api
```

### Response Format
ทุก API response เป็น JSON รูปแบบเดียวกัน:

**Success:**
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

**Error:**
```json
{
  "success": false,
  "message": "Error message",
  "errors": { ... }
}
```

---

## 🚀 เริ่มต้นใช้งาน

### ขั้นตอนที่ 1: ลงทะเบียน/Login

```javascript
// ลงทะเบียนผู้ใช้ใหม่
const register = async () => {
  const response = await fetch('http://localhost:8000/api/auth/register', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({
      name: 'John Doe',
      email: 'john@example.com',
      password: 'password123'
    })
  });

  const result = await response.json();

  if (result.success) {
    // เก็บ token
    localStorage.setItem('token', result.data.token);
    localStorage.setItem('user', JSON.stringify(result.data.user));
  }
};
```

### ขั้นตอนที่ 2: ใช้ Token เรียก API

```javascript
// สร้าง helper function
const apiCall = async (endpoint, options = {}) => {
  const token = localStorage.getItem('token');

  const response = await fetch(`http://localhost:8000/api${endpoint}`, {
    ...options,
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      ...options.headers
    }
  });

  return response.json();
};

// ใช้งาน
const programs = await apiCall('/programs');
```

---

## 🔐 Authentication

### การเก็บ Token

**DO:**
```javascript
// ✅ เก็บใน localStorage
localStorage.setItem('token', token);

// ✅ หรือใช้ state management (Redux, Zustand, etc.)
store.setToken(token);
```

**DON'T:**
```javascript
// ❌ ห้ามเก็บใน cookie ที่ไม่ secure
// ❌ ห้ามเก็บใน global variable
```

### ส่ง Token ทุกครั้ง

```javascript
headers: {
  'Authorization': `Bearer ${token}`,
  'Accept': 'application/json'
}
```

### ตรวจสอบ Token หมดอายุ

```javascript
const apiCall = async (endpoint, options = {}) => {
  const response = await fetch(url, options);

  if (response.status === 401) {
    // Token หมดอายุ - redirect ไป login
    localStorage.removeItem('token');
    window.location.href = '/login';
    return;
  }

  return response.json();
};
```

---

## 💡 ตัวอย่างการใช้งาน

### 1. ดูรายการ Programs

```javascript
// React Example
import { useState, useEffect } from 'react';

function ProgramList() {
  const [programs, setPrograms] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchPrograms = async () => {
      try {
        const result = await apiCall('/programs');

        if (result.success) {
          setPrograms(result.data);
        }
      } catch (error) {
        console.error('Error:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchPrograms();
  }, []);

  if (loading) return <div>Loading...</div>;

  return (
    <div>
      {programs.map(program => (
        <div key={program.id}>
          <h3>{program.name}</h3>
          <p>{program.description}</p>
          <p>Duration: {program.duration_hours} hours</p>
        </div>
      ))}
    </div>
  );
}
```

### 2. สร้าง Program (Form)

```javascript
// React Example with Form
import { useState } from 'react';

function CreateProgramForm() {
  const [formData, setFormData] = useState({
    name: '',
    code: '',
    category: '',
    duration_hours: '',
    status: 'active'
  });
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setErrors({});

    try {
      const result = await apiCall('/programs', {
        method: 'POST',
        body: JSON.stringify(formData)
      });

      if (result.success) {
        alert('Program created successfully!');
        // Reset form
        setFormData({
          name: '',
          code: '',
          category: '',
          duration_hours: '',
          status: 'active'
        });
      } else {
        // Validation errors
        setErrors(result.errors || {});
      }
    } catch (error) {
      console.error('Error:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      <div>
        <input
          type="text"
          placeholder="Program Name"
          value={formData.name}
          onChange={(e) => setFormData({...formData, name: e.target.value})}
        />
        {errors.name && <span className="error">{errors.name[0]}</span>}
      </div>

      <div>
        <input
          type="text"
          placeholder="Program Code"
          value={formData.code}
          onChange={(e) => setFormData({...formData, code: e.target.value})}
        />
        {errors.code && <span className="error">{errors.code[0]}</span>}
      </div>

      {/* More fields... */}

      <button type="submit" disabled={loading}>
        {loading ? 'Creating...' : 'Create Program'}
      </button>
    </form>
  );
}
```

### 3. Filter Sessions by Program

```javascript
// Vue Example
<template>
  <div>
    <select v-model="selectedProgramId" @change="fetchSessions">
      <option value="">All Programs</option>
      <option v-for="program in programs" :key="program.id" :value="program.id">
        {{ program.name }}
      </option>
    </select>

    <div v-for="session in sessions" :key="session.id">
      <h4>{{ session.title }}</h4>
      <p>{{ session.start_date }} - {{ session.end_date }}</p>
      <p>Capacity: {{ session.capacity }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const programs = ref([]);
const sessions = ref([]);
const selectedProgramId = ref('');

const fetchPrograms = async () => {
  const result = await apiCall('/programs');
  if (result.success) {
    programs.value = result.data;
  }
};

const fetchSessions = async () => {
  const endpoint = selectedProgramId.value
    ? `/sessions?program_id=${selectedProgramId.value}`
    : '/sessions';

  const result = await apiCall(endpoint);
  if (result.success) {
    sessions.value = result.data;
  }
};

onMounted(() => {
  fetchPrograms();
  fetchSessions();
});
</script>
```

### 4. Update Program

```javascript
// React Example
const updateProgram = async (programId, updates) => {
  try {
    const result = await apiCall(`/programs/${programId}`, {
      method: 'PUT',
      body: JSON.stringify(updates)
    });

    if (result.success) {
      console.log('Updated:', result.data);
      return result.data;
    }
  } catch (error) {
    console.error('Error:', error);
  }
};

// ใช้งาน
updateProgram(1, {
  name: 'Laravel Advanced (Updated)',
  status: 'inactive'
});
```

### 5. Delete Program

```javascript
// React Example with Confirmation
const deleteProgram = async (programId) => {
  if (!confirm('Are you sure you want to delete this program?')) {
    return;
  }

  try {
    const result = await apiCall(`/programs/${programId}`, {
      method: 'DELETE'
    });

    if (result.success) {
      alert('Program deleted successfully!');
      // Refresh list
      fetchPrograms();
    }
  } catch (error) {
    console.error('Error:', error);
  }
};
```

---

## ❌ Error Handling

### ตัวอย่าง Error Handler ที่ดี

```javascript
const handleAPIError = (error, response) => {
  // 401 - Unauthorized
  if (response.status === 401) {
    localStorage.removeItem('token');
    window.location.href = '/login';
    return;
  }

  // 403 - Forbidden
  if (response.status === 403) {
    alert('You don\'t have permission to perform this action');
    return;
  }

  // 404 - Not Found
  if (response.status === 404) {
    alert('Resource not found');
    return;
  }

  // 422 - Validation Error
  if (response.status === 422) {
    return error.errors; // Return validation errors to show in form
  }

  // 500 - Server Error
  if (response.status === 500) {
    alert('Server error. Please try again later.');
    return;
  }

  // Other errors
  alert('An error occurred: ' + error.message);
};

// ใช้งาน
try {
  const response = await fetch(url, options);
  const result = await response.json();

  if (!response.ok) {
    handleAPIError(result, response);
    return;
  }

  // Success
  console.log(result.data);
} catch (error) {
  console.error('Network error:', error);
}
```

### แสดง Validation Errors

```javascript
// React Example
function FormWithValidation() {
  const [errors, setErrors] = useState({});

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});

    try {
      const response = await fetch(url, options);
      const result = await response.json();

      if (response.status === 422) {
        // Set validation errors
        setErrors(result.errors);
        return;
      }

      if (result.success) {
        // Success
      }
    } catch (error) {
      console.error(error);
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      <input type="text" name="name" />
      {errors.name && (
        <div className="error">
          {errors.name.map((error, i) => (
            <p key={i}>{error}</p>
          ))}
        </div>
      )}
    </form>
  );
}
```

---

## ⭐ Best Practices

### 1. สร้าง API Service

```javascript
// api/service.js
class APIService {
  constructor(baseURL) {
    this.baseURL = baseURL;
  }

  async call(endpoint, options = {}) {
    const token = localStorage.getItem('token');

    const response = await fetch(`${this.baseURL}${endpoint}`, {
      ...options,
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        ...options.headers
      }
    });

    if (response.status === 401) {
      localStorage.removeItem('token');
      window.location.href = '/login';
      return;
    }

    return response.json();
  }

  // Programs
  async getPrograms() {
    return this.call('/programs');
  }

  async getProgram(id) {
    return this.call(`/programs/${id}`);
  }

  async createProgram(data) {
    return this.call('/programs', {
      method: 'POST',
      body: JSON.stringify(data)
    });
  }

  async updateProgram(id, data) {
    return this.call(`/programs/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data)
    });
  }

  async deleteProgram(id) {
    return this.call(`/programs/${id}`, {
      method: 'DELETE'
    });
  }

  // Sessions
  async getSessions(programId = null) {
    const endpoint = programId
      ? `/sessions?program_id=${programId}`
      : '/sessions';
    return this.call(endpoint);
  }
}

export default new APIService('http://localhost:8000/api');
```

### 2. ใช้ React Query / SWR

```javascript
// With React Query
import { useQuery, useMutation } from '@tanstack/react-query';
import api from './api/service';

function ProgramList() {
  // Fetch programs
  const { data, isLoading, error } = useQuery({
    queryKey: ['programs'],
    queryFn: () => api.getPrograms()
  });

  // Create program mutation
  const createMutation = useMutation({
    mutationFn: (newProgram) => api.createProgram(newProgram),
    onSuccess: () => {
      queryClient.invalidateQueries(['programs']);
    }
  });

  if (isLoading) return <div>Loading...</div>;
  if (error) return <div>Error: {error.message}</div>;

  return (
    <div>
      {data?.data?.map(program => (
        <div key={program.id}>{program.name}</div>
      ))}
    </div>
  );
}
```

### 3. Loading States

```javascript
function ComponentWithLoading() {
  const [loading, setLoading] = useState(false);
  const [data, setData] = useState(null);

  const fetchData = async () => {
    setLoading(true);
    try {
      const result = await api.getPrograms();
      setData(result.data);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div>
      {loading && <LoadingSpinner />}
      {!loading && data && <ProgramList programs={data} />}
    </div>
  );
}
```

### 4. Optimistic Updates

```javascript
// React Query Example
const updateMutation = useMutation({
  mutationFn: (updated) => api.updateProgram(updated.id, updated),
  onMutate: async (newData) => {
    // Cancel outgoing refetches
    await queryClient.cancelQueries(['programs']);

    // Snapshot previous value
    const previous = queryClient.getQueryData(['programs']);

    // Optimistically update
    queryClient.setQueryData(['programs'], (old) =>
      old.map(p => p.id === newData.id ? newData : p)
    );

    return { previous };
  },
  onError: (err, newData, context) => {
    // Rollback on error
    queryClient.setQueryData(['programs'], context.previous);
  },
  onSettled: () => {
    queryClient.invalidateQueries(['programs']);
  }
});
```

---

## 📚 เอกสารเพิ่มเติม

- [API Specification](./API_SPECIFICATION.md) - รายละเอียด API ทั้งหมด
- [Admin Guide](./ADMIN_GUIDE.md) - คู่มือ Admin
- [Testing Summary](../TESTING_SUMMARY.md) - การทดสอบ API

---

## 🎯 Quick Reference

### ส่วนที่ใช้บ่อย

```javascript
// Get all programs
api.getPrograms()

// Get sessions by program
api.getSessions(programId)

// Create program
api.createProgram({
  name: 'Program Name',
  code: 'CODE-001',
  category: 'Category',
  duration_hours: 40,
  status: 'active'
})

// Update program
api.updateProgram(id, { name: 'New Name' })

// Delete program
api.deleteProgram(id)
```

---

**Happy Coding! 🚀**

*Last Updated: 12 ธันวาคม 2025*
